<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Support\GeoFlow\ArticleWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleWorkflowSlugTest extends TestCase
{
    use RefreshDatabase;

    private Author $author;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::query()->create([
            'name' => 'Slug Tests',
            'slug' => 'slug-tests',
        ]);

        $this->author = Author::query()->create([
            'name' => 'Slug Test Author',
        ]);
    }

    public function test_it_generates_readable_slug_from_title(): void
    {
        $slug = ArticleWorkflow::generateUniqueSlug('GPT88 Sub Site Build Guide');

        $this->assertSame('gpt88-sub-site-build-guide', $slug);
    }

    public function test_it_appends_counter_when_slug_is_already_taken(): void
    {
        $this->createArticle('GPT88 Sub Site Build Guide', 'gpt88-sub-site-build-guide');

        $slug = ArticleWorkflow::generateUniqueSlug('GPT88 Sub Site Build Guide');

        $this->assertSame('gpt88-sub-site-build-guide-2', $slug);
    }

    public function test_it_can_ignore_current_article_when_checking_slug_uniqueness(): void
    {
        $article = $this->createArticle('GPT88 Sub Site Build Guide', 'gpt88-sub-site-build-guide');

        $slug = ArticleWorkflow::generateUniqueSlug('GPT88 Sub Site Build Guide', (int) $article->id);

        $this->assertSame('gpt88-sub-site-build-guide', $slug);
    }

    public function test_it_generates_a_url_safe_slug_when_title_cannot_be_slugified_directly(): void
    {
        $slug = ArticleWorkflow::generateUniqueSlug('纯中文标题');

        $this->assertNotSame('', $slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug);
    }

    private function createArticle(string $title, string $slug): Article
    {
        return Article::query()->create([
            'title' => $title,
            'slug' => $slug,
            'content' => 'Test content',
            'category_id' => $this->category->id,
            'author_id' => $this->author->id,
            'status' => 'draft',
            'review_status' => 'pending',
        ]);
    }
}
