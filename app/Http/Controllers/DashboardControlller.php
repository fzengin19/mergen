<?php

namespace App\Http\Controllers;

use App\Workflows\InstagramContentResearchWorkflow;
use App\Jobs\RunInstagramContentResearchWorkflow;
use Illuminate\Http\Request;
use App\Models\Research;
use App\Dtos\StartResearchDto;
use App\Workflows\DeepResearchWorkflow;
use Illuminate\Support\Facades\Auth;

class DashboardControlller extends Controller
{
    /**
     * Show the dashboard
     */
    public function dashboard()
    {
        $researches = Research::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('dashboard', compact('researches'));
    }

    /**
     * Start a new research
     */
    public function startResearch(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:1000',
            'additional_info' => 'nullable|string|max:2000',
            'target_audience' => 'nullable|string|max:500',
            'content_type' => 'nullable|string|max:50',
            'tone' => 'nullable|string|max:50',
        ]);

        // Research kaydını oluştur
        $research = Research::create([
            'user_id' => Auth::id(),
            'title' => $request->input('title'),
            'additional_info' => $request->input('additional_info', ''),
            'target_audience' => $request->input('target_audience', ''),
            'content_type' => $request->input('content_type', 'post'),
            'tone' => $request->input('tone', 'casual'),
            'status' => 'pending',
        ]);

        // Workflow'u arka planda başlat (queue job)
        try {
            // İsteğe bağlı: statüyü hemen queued yap
            $research->update(['status' => 'queued']);
            
            RunInstagramContentResearchWorkflow::dispatch($research->id);
            
            return redirect()->route('dashboard')->with('success', 'Araştırma kuyruğa alındı ve arka planda başlatıldı.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Workflow başlatma hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('dashboard')->with('error', 'Araştırma kuyruğa alınırken bir hata oluştu: ' . $e->getMessage());
        }
    }

    /**
     * Show research details
     */
    public function showResearch(Research $research)
    {
        // Kullanıcının kendi research'ini görebilmesini sağla
        if ($research->user_id !== Auth::id()) {
            abort(403);
        }

        return view('research.show', compact('research'));
    }
}
