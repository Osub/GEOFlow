@extends('admin.layouts.app')

@php
    $i18nRoot = $isEdit ? 'admin.article_edit' : 'admin.article_create';
    $formAction = $isEdit
        ? route('admin.articles.update', ['articleId' => (int) $articleId])
        : route('admin.articles.store');

    $formData = [
        'title' => old('title', (string) ($articleForm['title'] ?? '')),
        'excerpt' => old('excerpt', (string) ($articleForm['excerpt'] ?? '')),
        'content' => old('content', (string) ($articleForm['content'] ?? '')),
        'keywords' => old('keywords', (string) ($articleForm['keywords'] ?? '')),
        'meta_description' => old('meta_description', (string) ($articleForm['meta_description'] ?? '')),
        'status' => old('status', (string) ($articleForm['status'] ?? 'draft')),
        'review_status' => old('review_status', (string) ($articleForm['review_status'] ?? 'pending')),
        'category_id' => old('category_id', (string) ($articleForm['category_id'] ?? '')),
        'author_id' => old('author_id', (string) ($articleForm['author_id'] ?? '')),
        'slug' => (string) ($articleForm['slug'] ?? ''),
        'published_at' => (string) ($articleForm['published_at'] ?? ''),
        'task_name' => (string) ($articleForm['task_name'] ?? ''),
        'is_hot' => old('is_hot', !empty($articleForm['is_hot']) ? '1' : '0'),
        'is_featured' => old('is_featured', !empty($articleForm['is_featured']) ? '1' : '0'),
    ];
@endphp

@section('content')
    <div class="px-4 sm:px-0">
        <div class="flex items-center space-x-4 mb-6">
            <a href="{{ route('admin.articles.index') }}" class="text-gray-400 hover:text-gray-600">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __($i18nRoot.'.page_heading') }}</h1>
                <p class="mt-1 text-sm text-gray-600">
                    @if($isEdit)
                        {{ $formData['title'] }}
                    @else
                        {{ __($i18nRoot.'.page_subtitle') }}
                    @endif
                </p>
            </div>
        </div>

        <form method="POST" action="{{ $formAction }}" class="space-y-8">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <div class="lg:col-span-3 space-y-6">
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">{{ __($i18nRoot.'.section.basic_title') }}</h3>
                        </div>
                        <div class="px-6 py-4 space-y-6">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700">{{ __($i18nRoot.'.field.title') }} *</label>
                                <input id="title" type="text" name="title" required value="{{ $formData['title'] }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="{{ __($i18nRoot.'.placeholder.title') }}">
                            </div>
                            <div>
                                <label for="excerpt" class="block text-sm font-medium text-gray-700">{{ __($i18nRoot.'.field.excerpt') }}</label>
                                <textarea id="excerpt" name="excerpt" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="{{ __($i18nRoot.'.placeholder.excerpt') }}">{{ $formData['excerpt'] }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white shadow rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900">{{ __($i18nRoot.'.section.content_title') }}</h3>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-500">{{ __($i18nRoot.'.help.markdown_supported') }}</span>
                                    <button type="button" id="content-preview-toggle" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                        <i data-lucide="eye" class="w-4 h-4 mr-1"></i>
                                        <span id="preview-toggle-text">{{ __($i18nRoot.'.button.show_preview') }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-4">
                            <div class="mb-3 flex flex-wrap items-center gap-1 rounded-md border border-gray-200 bg-gray-50 p-2" aria-label="{{ __($i18nRoot.'.toolbar.label') }}">
                                <button type="button" data-editor-insert="bold" class="editor-tool-button" title="{{ __($i18nRoot.'.toolbar.bold') }}" aria-label="{{ __($i18nRoot.'.toolbar.bold') }}">
                                    <strong>B</strong>
                                </button>
                                <button type="button" data-editor-insert="italic" class="editor-tool-button" title="{{ __($i18nRoot.'.toolbar.italic') }}" aria-label="{{ __($i18nRoot.'.toolbar.italic') }}">
                                    <em>I</em>
                                </button>
                                <button type="button" data-editor-insert="link" class="editor-tool-button" title="{{ __($i18nRoot.'.toolbar.link') }}" aria-label="{{ __($i18nRoot.'.toolbar.link') }}">
                                    <i data-lucide="link" class="w-4 h-4"></i>
                                </button>
                                <button type="button" data-editor-insert="image" class="editor-tool-button" title="{{ __($i18nRoot.'.toolbar.image') }}" aria-label="{{ __($i18nRoot.'.toolbar.image') }}">
                                    <i data-lucide="image" class="w-4 h-4"></i>
                                </button>
                                <button type="button" data-editor-insert="table" class="editor-tool-button" title="{{ __($i18nRoot.'.toolbar.table') }}" aria-label="{{ __($i18nRoot.'.toolbar.table') }}">
                                    <i data-lucide="table" class="w-4 h-4"></i>
                                </button>
                                <button type="button" data-editor-insert="html" class="editor-tool-button" title="{{ __($i18nRoot.'.toolbar.html') }}" aria-label="{{ __($i18nRoot.'.toolbar.html') }}">
                                    <i data-lucide="code-xml" class="w-4 h-4"></i>
                                </button>
                            </div>
                            <textarea id="content-textarea" name="content" required class="block w-full h-96 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm editor-textarea" placeholder="{{ __($i18nRoot.'.placeholder.content') }}">{{ $formData['content'] }}</textarea>
                            <div id="content-preview-panel" class="hidden" aria-hidden="true">
                                <div id="content-preview" class="markdown-preview-pane"></div>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">{{ __($i18nRoot.'.help.html_safety') }}</p>
                        </div>
                    </div>

                    <div class="bg-white shadow rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">{{ __($i18nRoot.'.section.seo_title') }}</h3>
                        </div>
                        <div class="px-6 py-4 space-y-6">
                            <div>
                                <label for="keywords" class="block text-sm font-medium text-gray-700">{{ __($i18nRoot.'.field.keywords') }}</label>
                                <input id="keywords" type="text" name="keywords" value="{{ $formData['keywords'] }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="{{ __($i18nRoot.'.placeholder.keywords') }}">
                            </div>
                            <div>
                                <label for="meta_description" class="block text-sm font-medium text-gray-700">{{ __($i18nRoot.'.field.meta_description') }}</label>
                                <textarea id="meta_description" name="meta_description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="{{ __($i18nRoot.'.placeholder.meta_description') }}">{{ $formData['meta_description'] }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">{{ __($i18nRoot.'.section.publish_title') }}</h3>
                        </div>
                        <div class="px-6 py-4 space-y-4">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">{{ __($i18nRoot.'.field.publish_status') }}</label>
                                <select id="status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="draft" @selected($formData['status'] === 'draft')>{{ __('admin.articles.status.draft') }}</option>
                                    <option value="published" @selected($formData['status'] === 'published')>{{ __('admin.articles.status.published') }}</option>
                                    <option value="private" @selected($formData['status'] === 'private')>{{ __('admin.articles.status.private') }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="review_status" class="block text-sm font-medium text-gray-700">{{ __($i18nRoot.'.field.review_status') }}</label>
                                <select id="review_status" name="review_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="pending" @selected($formData['review_status'] === 'pending')>{{ __('admin.articles.review.pending') }}</option>
                                    <option value="approved" @selected($formData['review_status'] === 'approved')>{{ __('admin.articles.review.approved') }}</option>
                                    <option value="rejected" @selected($formData['review_status'] === 'rejected')>{{ __('admin.articles.review.rejected') }}</option>
                                    <option value="auto_approved" @selected($formData['review_status'] === 'auto_approved')>{{ __('admin.articles.review.auto_approved') }}</option>
                                </select>
                                <p class="mt-2 text-xs text-gray-500">{{ __($i18nRoot.'.help.review_status') }}</p>
                            </div>
                            <div class="rounded-lg border border-blue-100 bg-blue-50/70 p-4">
                                <div class="text-sm font-medium text-gray-900">{{ __($i18nRoot.'.section.recommendation_title') }}</div>
                                <p class="mt-1 text-xs text-gray-600">{{ __($i18nRoot.'.help.recommendation') }}</p>
                                <div class="mt-3 space-y-3">
                                    <label class="flex items-start gap-3 text-sm text-gray-700">
                                        <input type="checkbox" name="is_hot" value="1" @checked((string) $formData['is_hot'] === '1') class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                        <span>
                                            <span class="font-medium text-gray-900">{{ __($i18nRoot.'.field.is_hot') }}</span>
                                            <span class="block text-xs text-gray-500">{{ __($i18nRoot.'.help.is_hot') }}</span>
                                        </span>
                                    </label>
                                    <label class="flex items-start gap-3 text-sm text-gray-700">
                                        <input type="checkbox" name="is_featured" value="1" @checked((string) $formData['is_featured'] === '1') class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                        <span>
                                            <span class="font-medium text-gray-900">{{ __($i18nRoot.'.field.is_featured') }}</span>
                                            <span class="block text-xs text-gray-500">{{ __($i18nRoot.'.help.is_featured') }}</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white shadow rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">{{ __($i18nRoot.'.section.category_author_title') }}</h3>
                        </div>
                        <div class="px-6 py-4 space-y-4">
                            <div>
                                <label for="category_id" class="block text-sm font-medium text-gray-700">{{ __($i18nRoot.'.field.category') }} *</label>
                                <select id="category_id" name="category_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">{{ __($i18nRoot.'.option.select_category') }}</option>
                                    @foreach(($formOptions['categories'] ?? []) as $category)
                                        <option value="{{ (int) $category['id'] }}" @selected($formData['category_id'] === (string) $category['id'])>{{ $category['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="author_id" class="block text-sm font-medium text-gray-700">{{ __($i18nRoot.'.field.author') }} *</label>
                                <select id="author_id" name="author_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <option value="">{{ __($i18nRoot.'.option.select_author') }}</option>
                                    @foreach(($formOptions['authors'] ?? []) as $author)
                                        <option value="{{ (int) $author['id'] }}" @selected($formData['author_id'] === (string) $author['id'])>{{ $author['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    @if($isEdit)
                        <div class="bg-white shadow rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('admin.article_edit.section.info_title') }}</h3>
                            </div>
                            <div class="px-6 py-4 text-sm text-gray-600 space-y-2">
                                <div>{{ __('admin.article_edit.info.article_id') }}: #{{ (int) $articleId }}</div>
                                <div>{{ __('admin.article_edit.info.slug') }}: {{ $formData['slug'] }}</div>
                                <div>{{ __('admin.article_edit.info.source_task') }}: {{ $formData['task_name'] !== '' ? $formData['task_name'] : __('admin.article_edit.info.manual_source') }}</div>
                                <div>{{ __('admin.article_edit.info.published_at') }}: {{ $formData['published_at'] !== '' ? $formData['published_at'] : '-' }}</div>
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-end space-x-3">
                        <a href="{{ route('admin.articles.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            {{ __('admin.button.cancel') }}
                        </a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            {{ $isEdit ? __('admin.article_edit.button.save_changes') : __('admin.button.create_article') }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        .editor-textarea {
            resize: vertical;
            font-family: Monaco, Menlo, "Ubuntu Mono", monospace;
            font-size: 14px;
            line-height: 1.5;
        }
        .editor-tool-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 0.375rem;
            color: #374151;
            background: #fff;
            border: 1px solid #d1d5db;
            font-size: 0.875rem;
            line-height: 1;
        }
        .editor-tool-button:hover {
            color: #1d4ed8;
            border-color: #93c5fd;
            background: #eff6ff;
        }
        /* 与输入框 h-96 同高，内容仅在框内滚动，不撑破下方 SEO 区域 */
        .markdown-preview-pane {
            height: 24rem;
            box-sizing: border-box;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 1rem 1.125rem;
            background-color: #f9fafb;
            overflow-y: auto;
            overflow-x: auto;
            line-height: 1.65;
            word-break: break-word;
            font-size: 0.9375rem;
        }
        .markdown-preview-pane :where(h1, h2, h3, h4, h5, h6) {
            font-weight: 600;
            margin: 0.85em 0 0.4em;
            line-height: 1.35;
        }
        .markdown-preview-pane :where(p, ul, ol, pre, blockquote) {
            margin: 0.55em 0;
        }
        .markdown-preview-pane pre {
            padding: 0.75rem 1rem;
            overflow-x: auto;
            border-radius: 0.375rem;
            background: #fff;
            border: 1px solid #e5e7eb;
        }
    </style>
@endpush

@push('scripts')
    <script>
        let previewVisible = false;
        const previewAllowedTags = new Set([
            'a', 'article', 'b', 'blockquote', 'br', 'code', 'del', 'div', 'em', 'figcaption',
            'figure', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'img', 'ins',
            'kbd', 'li', 'mark', 'ol', 'p', 'pre', 's', 'samp', 'small', 'span',
            'section', 'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'th', 'thead', 'tr',
            'u', 'ul',
        ]);
        const previewRemoveWithContent = new Set([
            'base', 'button', 'embed', 'form', 'iframe', 'input', 'link', 'math',
            'meta', 'object', 'option', 'script', 'select', 'style', 'svg',
            'textarea',
        ]);
        const previewGlobalAttributes = new Set(['aria-label', 'title']);
        const previewTagAttributes = {
            a: new Set(['href', 'rel', 'target', 'title']),
            code: new Set(['class']),
            img: new Set(['alt', 'height', 'src', 'title', 'width']),
            ol: new Set(['start', 'type']),
            td: new Set(['colspan', 'rowspan']),
            th: new Set(['colspan', 'rowspan']),
        };

        function isSafePreviewUrl(url, image) {
            const value = String(url || '').trim().replace(/[\u0000-\u0020\u007f]+/g, '');
            if (!value) {
                return false;
            }
            if (value.startsWith('#') || value.startsWith('/') || value.startsWith('./') || value.startsWith('../')) {
                return true;
            }
            try {
                const parsed = new URL(value, window.location.origin);
                const allowed = image ? ['http:', 'https:'] : ['http:', 'https:', 'mailto:', 'tel:'];
                return allowed.includes(parsed.protocol);
            } catch (error) {
                return !/^[a-z][a-z0-9+.-]*:/i.test(value);
            }
        }

        function sanitizePreviewAttributes(element, tag) {
            Array.from(element.attributes).forEach(function (attribute) {
                const name = attribute.name.toLowerCase();
                const value = attribute.value.trim();
                const tagAllowed = previewTagAttributes[tag] || new Set();

                if (name.startsWith('on') || name.includes(':') || (!previewGlobalAttributes.has(name) && !tagAllowed.has(name))) {
                    element.removeAttribute(attribute.name);
                    return;
                }

                if (name === 'href' && !isSafePreviewUrl(value, false)) {
                    element.removeAttribute(attribute.name);
                    return;
                }

                if (name === 'src' && !isSafePreviewUrl(value, true)) {
                    element.removeAttribute(attribute.name);
                    return;
                }

                if (['height', 'width', 'colspan', 'rowspan', 'start'].includes(name) && !/^\d{1,4}$/.test(value)) {
                    element.removeAttribute(attribute.name);
                    return;
                }

                if (name === 'class') {
                    const classes = value.split(/\s+/).filter(function (className) {
                        return /^language-[a-z0-9_-]+$/i.test(className);
                    });
                    if (tag !== 'code' || classes.length === 0) {
                        element.removeAttribute(attribute.name);
                        return;
                    }
                    element.setAttribute('class', classes.join(' '));
                }

                if (name === 'target' && !['_blank', '_self', '_parent', '_top'].includes(value)) {
                    element.removeAttribute(attribute.name);
                }
            });

            if (tag === 'a' && element.getAttribute('target') === '_blank') {
                element.setAttribute('rel', 'noopener noreferrer');
            }
        }

        function unwrapPreviewElement(element) {
            const parent = element.parentNode;
            while (element.firstChild) {
                parent.insertBefore(element.firstChild, element);
            }
            parent.removeChild(element);
        }

        function sanitizePreviewTree(parent) {
            Array.from(parent.children).forEach(function (element) {
                const tag = element.tagName.toLowerCase();
                if (previewRemoveWithContent.has(tag)) {
                    element.remove();
                    return;
                }
                if (!previewAllowedTags.has(tag)) {
                    sanitizePreviewTree(element);
                    unwrapPreviewElement(element);
                    return;
                }
                sanitizePreviewAttributes(element, tag);
                sanitizePreviewTree(element);
                if (tag === 'img' && !element.hasAttribute('src')) {
                    element.remove();
                }
            });
        }

        function sanitizePreviewHtml(html) {
            const parser = new DOMParser();
            const document = parser.parseFromString('<div>' + html + '</div>', 'text/html');
            const root = document.body.firstElementChild;
            if (!root) {
                return '';
            }
            sanitizePreviewTree(root);
            return root.innerHTML;
        }

        function renderPreview() {
            const source = document.getElementById('content-textarea');
            const target = document.getElementById('content-preview');
            if (!source || !target || typeof marked === 'undefined') {
                return;
            }
            target.innerHTML = sanitizePreviewHtml(marked.parse(source.value || ''));
        }

        function insertEditorSnippet(kind) {
            const textarea = document.getElementById('content-textarea');
            if (!textarea) {
                return;
            }

            const start = textarea.selectionStart || 0;
            const end = textarea.selectionEnd || 0;
            const selected = textarea.value.slice(start, end);
            const snippets = {
                bold: ['**' + (selected || 'Bold text') + '**', selected ? start + 2 : start + 2, selected ? end + 2 : start + 11],
                italic: ['*' + (selected || 'Italic text') + '*', selected ? start + 1 : start + 1, selected ? end + 1 : start + 12],
                link: ['[' + (selected || 'Link text') + '](https://example.com)', selected ? start + 1 : start + 1, selected ? end + 1 : start + 10],
                image: ['![' + (selected || 'Image alt') + '](https://example.com/image.jpg)', selected ? start + 2 : start + 2, selected ? end + 2 : start + 11],
                table: ["\n| Column | Value |\n| --- | --- |\n| Item | Detail |\n", start + 3, start + 9],
                html: ['\n<section>\n  <h2>' + (selected || 'Section heading') + '</h2>\n  <p>Paragraph text</p>\n</section>\n', selected ? start + 17 : start + 17, selected ? start + 17 + selected.length : start + 32],
            };
            const snippet = snippets[kind];
            if (!snippet) {
                return;
            }

            textarea.value = textarea.value.slice(0, start) + snippet[0] + textarea.value.slice(end);
            textarea.focus();
            textarea.setSelectionRange(snippet[1], snippet[2]);
            if (previewVisible) {
                renderPreview();
            }
        }

        function togglePreview() {
            const textarea = document.getElementById('content-textarea');
            const panel = document.getElementById('content-preview-panel');
            const toggleText = document.getElementById('preview-toggle-text');

            if (!textarea || !panel || !toggleText) {
                return;
            }

            previewVisible = !previewVisible;
            if (previewVisible) {
                renderPreview();
                textarea.classList.add('hidden');
                panel.classList.remove('hidden');
                panel.setAttribute('aria-hidden', 'false');
                toggleText.textContent = @json(__($i18nRoot.'.button.hide_preview'));
            } else {
                textarea.classList.remove('hidden');
                panel.classList.add('hidden');
                panel.setAttribute('aria-hidden', 'true');
                toggleText.textContent = @json(__($i18nRoot.'.button.show_preview'));
            }
        }

        document.getElementById('content-preview-toggle')?.addEventListener('click', togglePreview);
        document.querySelectorAll('[data-editor-insert]').forEach(function (button) {
            button.addEventListener('click', function () {
                insertEditorSnippet(button.dataset.editorInsert || '');
            });
        });
    </script>
@endpush
