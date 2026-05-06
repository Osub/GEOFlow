@extends('theme.toutiao-news-20260426.layout')

@push('head')
    @php
        $schemaAtContext = chr(64).'context';
        $schemaAtType = chr(64).'type';
        $schemaAtId = chr(64).'id';
        $articleSchema = [
            $schemaAtContext => 'https://schema.org',
            $schemaAtType => 'NewsArticle',
            'headline' => $article->title,
            'description' => $pageDescription,
            'datePublished' => optional($article->published_at ?? $article->created_at)->toAtomString(),
            'dateModified' => optional($article->updated_at ?? $article->published_at ?? $article->created_at)->toAtomString(),
            'mainEntityOfPage' => [
                $schemaAtType => 'WebPage',
                $schemaAtId => $canonicalUrl ?? route('site.article', $article->slug),
            ],
            'author' => [
                $schemaAtType => 'Person',
                'name' => $article->author?->name ?? $siteTitle,
            ],
            'publisher' => [
                $schemaAtType => 'Organization',
                'name' => $siteTitle,
            ],
            'articleSection' => $article->category?->name,
            'keywords' => $tags,
        ];
    @endphp
    <meta property="og:title" content="{{ $article->title }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ $canonicalUrl ?? route('site.article', $article->slug) }}">
    @if($article->category)
        <meta property="article:section" content="{{ $article->category->name }}">
    @endif
    <script type="application/ld+json">
        {!! json_encode($articleSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endpush

@section('content')
    <div class="tt-shell tt-article-layout">
        <nav class="tt-breadcrumb tt-article-module" aria-label="Breadcrumb">
            <a href="{{ route('site.home') }}">{{ __('front.nav.home') }}</a>
            @if($article->category)
                <span>/</span>
                <a href="{{ route('site.category', $article->category->slug) }}">{{ $article->category->name }}</a>
            @endif
            <span>/</span>
            <span>{{ $article->title }}</span>
        </nav>

        <article class="tt-article-main tt-article-module">
            <div class="tt-card-meta">
                @if($article->category)
                    <a href="{{ route('site.category', $article->category->slug) }}" class="tt-pill">{{ $article->category->name }}</a>
                @endif
                <time datetime="{{ ($article->published_at ?? $article->created_at)?->toAtomString() }}">
                    {{ ($article->published_at ?? $article->created_at)?->format('Y-m-d') }}
                </time>
                @if($article->author)
                    <span>{{ $article->author->name }}</span>
                @endif
                <span>{{ (int) $article->view_count }} views</span>
            </div>

            <h1 class="tt-article-h1 mt-4">{{ $article->title }}</h1>

            @if($excerptPlain !== '')
                <p class="mt-5 rounded-2xl bg-gray-50 p-5 text-lg leading-8 text-gray-600">{{ $excerptPlain }}</p>
            @endif

            <div class="tt-prose">
                {!! $contentHtml !!}
            </div>

            @if(!empty($tags))
                <div class="mt-10 flex flex-wrap gap-2">
                    @foreach($tags as $tag)
                        <span class="tt-pill">{{ $tag }}</span>
                    @endforeach
                </div>
            @endif
        </article>

        @if($relatedArticles->isNotEmpty())
            <section class="tt-related-block tt-article-module">
                <div class="tt-section-title">
                    <span class="tt-title-row">{{ __('site.article_related') }}</span>
                </div>
                <div class="tt-related-grid">
                    @foreach($relatedArticles as $related)
                        <a href="{{ route('site.article', $related->slug) }}" class="tt-related-card">
                            <span class="tt-related-index">{{ $loop->iteration }}</span>
                            <span>{{ $related->title }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <aside class="tt-sidebar">
            @if($relatedArticles->isNotEmpty())
                <section class="tt-panel">
                    <div class="tt-section-title">
                        <span class="tt-title-row">{{ __('site.article_related') }}</span>
                    </div>
                    <div class="tt-hot-list">
                        @foreach($relatedArticles as $related)
                            <a href="{{ route('site.article', $related->slug) }}" class="tt-hot-item">
                                <span class="tt-hot-index">{{ $loop->iteration }}</span>
                                <span>{{ $related->title }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="tt-panel">
                <div class="tt-section-title">
                    <span class="tt-title-row">{{ $siteTitle }}</span>
                </div>
                <p class="text-sm leading-7 text-gray-600">{{ $siteDescription }}</p>
                <a href="{{ route('site.home') }}" class="tt-card-action">{{ __('front.nav.home') }} <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
            </section>
        </aside>
    </div>

    @if($stickyAd)
        <aside id="articleStickyAd" class="article-sticky-ad" data-ad-id="{{ $stickyAd['id'] }}">
            <div class="article-sticky-ad__inner">
                <button type="button" class="article-sticky-ad__close" id="articleStickyAdClose" aria-label="{{ __('site.article_ad_close') }}">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
                <div class="article-sticky-ad__content">
                    @if($stickyAd['badge'] !== '')
                        <div class="article-sticky-ad__badge">{{ $stickyAd['badge'] }}</div>
                    @endif
                    @if($stickyAd['title'] !== '')
                        <h3 class="article-sticky-ad__title">{{ $stickyAd['title'] }}</h3>
                    @endif
                    <p class="article-sticky-ad__copy">{{ $stickyAd['copy'] }}</p>
                </div>
                @if(($stickyAd['qr_code_url'] ?? '') !== '')
                    <div class="article-sticky-ad__qr">
                        <img src="{{ $stickyAd['qr_code_url'] }}" alt="{{ ($stickyAd['qr_code_label'] ?? '') !== '' ? $stickyAd['qr_code_label'] : $stickyAd['button_text'] }}" loading="lazy">
                        @if(($stickyAd['qr_code_label'] ?? '') !== '')
                            <span>{{ $stickyAd['qr_code_label'] }}</span>
                        @endif
                    </div>
                @endif
                <a href="{{ $stickyAd['button_url'] }}" class="article-sticky-ad__button">
                    {{ $stickyAd['button_text'] }}
                    <i data-lucide="arrow-up-right" class="w-4 h-4 ml-2"></i>
                </a>
            </div>
        </aside>
    @endif
@endsection

@if($stickyAd)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const stickyAd = document.getElementById('articleStickyAd');
                const closeButton = document.getElementById('articleStickyAdClose');
                if (!stickyAd || !closeButton) {
                    return;
                }
                const storageKey = 'articleStickyAdDismissed:' + (stickyAd.dataset.adId || 'default');
                if (window.localStorage && localStorage.getItem(storageKey) === '1') {
                    stickyAd.remove();
                    return;
                }
                closeButton.addEventListener('click', function () {
                    if (window.localStorage) {
                        localStorage.setItem(storageKey, '1');
                    }
                    stickyAd.remove();
                });
            });
        </script>
    @endpush
@endif
