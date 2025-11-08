@extends('layouts.app')

@section('title', 'Araştırma Detayları - Mergen')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:text-indigo-900 mr-4">
                        ← Dashboard'a Dön
                    </a>
                    <h1 class="text-xl font-semibold text-gray-900">Araştırma Detayları</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-700">{{ Auth::user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-md text-sm font-medium">
                            Çıkış Yap
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg">
            <!-- Research Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900">{{ $research->title }}</h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Oluşturulma: {{ $research->created_at->format('d.m.Y H:i') }}
                        </p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            İşleniyor
                        </span>
                    </div>
                </div>
            </div>

            <!-- Research Content -->
            <div class="px-6 py-4">
                @if($research->additional_info)
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Ek Bilgiler</h3>
                        <div class="bg-gray-50 rounded-md p-4">
                            <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $research->additional_info }}</p>
                        </div>
                    </div>
                @endif

                <!-- Research Status -->
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Araştırma Durumu</h3>
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.725-1.36 3.49 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-800">
                                    Araştırma işlemi başlatıldı ve şu anda işleniyor. Sonuçlar hazır olduğunda bu sayfada görüntülenecektir.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Placeholder for Research Results -->
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Araştırma Sonuçları</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Araştırma tamamlandığında sonuçlar burada görüntülenecektir.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
