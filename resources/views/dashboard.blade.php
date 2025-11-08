@extends('layouts.app')

@section('title', 'Dashboard - Mergen')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-semibold text-gray-900">Mergen Deep Research</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-700">Hoş geldiniz, {{ Auth::user()->name }}</span>
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
        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-md mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-md mb-6">
                {{ session('error') }}
            </div>
        @endif

        <!-- Research Form -->
        <div class="bg-white shadow rounded-lg p-6 mb-8">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Yeni Araştırma Başlat</h2>
            <form method="POST" action="{{ route('research.start') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">Araştırma Konusu</label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           value="{{ old('title') }}"
                           placeholder="Araştırmak istediğiniz konuyu yazın..."
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('title') border-red-300 @enderror"
                           required>
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="additional_info" class="block text-sm font-medium text-gray-700">Ek Bilgiler (Opsiyonel)</label>
                    <textarea id="additional_info" 
                              name="additional_info" 
                              rows="3"
                              placeholder="Araştırma için ek bilgiler, özel istekler veya notlar..."
                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('additional_info') border-red-300 @enderror">{{ old('additional_info') }}</textarea>
                    @error('additional_info')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="target_audience" class="block text-sm font-medium text-gray-700">Hedef Kitle</label>
                        <input type="text" 
                               id="target_audience" 
                               name="target_audience" 
                               value="{{ old('target_audience') }}"
                               placeholder="Gençler, profesyoneller..."
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('target_audience') border-red-300 @enderror">
                        @error('target_audience')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="content_type" class="block text-sm font-medium text-gray-700">İçerik Türü</label>
                        <select id="content_type" 
                                name="content_type" 
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('content_type') border-red-300 @enderror">
                            <option value="post" {{ old('content_type') == 'post' ? 'selected' : '' }}>Post</option>
                            <option value="reel" {{ old('content_type') == 'reel' ? 'selected' : '' }}>Reel</option>
                            <option value="story" {{ old('content_type') == 'story' ? 'selected' : '' }}>Story</option>
                            <option value="carousel" {{ old('content_type') == 'carousel' ? 'selected' : '' }}>Carousel</option>
                        </select>
                        @error('content_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="tone" class="block text-sm font-medium text-gray-700">Ton</label>
                        <select id="tone" 
                                name="tone" 
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('tone') border-red-300 @enderror">
                            <option value="casual" {{ old('tone') == 'casual' ? 'selected' : '' }}>Samimi</option>
                            <option value="professional" {{ old('tone') == 'professional' ? 'selected' : '' }}>Profesyonel</option>
                            <option value="educational" {{ old('tone') == 'educational' ? 'selected' : '' }}>Eğitici</option>
                            <option value="entertaining" {{ old('tone') == 'entertaining' ? 'selected' : '' }}>Eğlenceli</option>
                        </select>
                        @error('tone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Araştırmayı Başlat
                    </button>
                </div>
            </form>
        </div>

        <!-- Research List -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Araştırma Geçmişi</h3>
            </div>
            
            @if($researches->count() > 0)
                <div class="divide-y divide-gray-200">
                    @foreach($researches as $research)
                        <div class="px-6 py-4 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-gray-900">{{ $research->title }}</h4>
                                    <p class="text-sm text-gray-500 mt-1">
                                        {{ $research->created_at->format('d.m.Y H:i') }}
                                    </p>
                                    @if($research->additional_info)
                                        <p class="text-sm text-gray-600 mt-2">{{ Str::limit($research->additional_info, 100) }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        İşleniyor
                                    </span>
                                    <a href="{{ route('research.show', $research) }}" 
                                       class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                        Detayları Gör
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                @if($researches->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $researches->links() }}
                    </div>
                @endif
            @else
                <div class="px-6 py-8 text-center">
                    <p class="text-gray-500">Henüz araştırma başlatmadınız.</p>
                    <p class="text-sm text-gray-400 mt-1">Yukarıdaki formu kullanarak ilk araştırmanızı başlatabilirsiniz.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
