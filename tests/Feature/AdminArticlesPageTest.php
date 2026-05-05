<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\SiteSetting;
use App\Support\AdminWeb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 后台文章页（Blade）最小可用测试：鉴权、列表渲染、创建/编辑页路由。
 */
class AdminArticlesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login_when_visiting_articles_page(): void
    {
        $this->get(route('admin.articles.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_authenticated_admin_can_view_articles_page(): void
    {
        $admin = Admin::query()->create([
            'username' => 'articles_admin',
            'password' => 'secret-123',
            'email' => 'articles-admin@example.com',
            'display_name' => 'Articles Admin',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.articles.index', ['status' => 'draft']))
            ->assertOk()
            ->assertSee(__('admin.articles.page_title'))
            ->assertViewHas('articles')
            ->assertViewHas('filters');
    }

    public function test_authenticated_admin_can_open_article_create_page(): void
    {
        $admin = Admin::query()->create([
            'username' => 'articles_create_admin',
            'password' => 'secret-123',
            'email' => 'articles-create-admin@example.com',
            'display_name' => 'Articles Admin',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.articles.create'))
            ->assertOk()
            ->assertSee(__('admin.article_create.page_heading'));
    }

    public function test_admin_can_save_article_hot_and_featured_flags(): void
    {
        $admin = Admin::query()->create([
            'username' => 'articles_flags_admin',
            'password' => 'secret-123',
            'email' => 'articles-flags@example.com',
            'display_name' => 'Articles Flags Admin',
            'role' => 'admin',
            'status' => 'active',
        ]);
        $category = Category::query()->create([
            'name' => '科技资讯',
            'slug' => 'tech',
        ]);
        $author = Author::query()->create([
            'name' => 'GEOFlow',
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.articles.store'), [
                'title' => '推荐标记测试文章',
                'excerpt' => '摘要',
                'content' => '正文',
                'keywords' => 'GEO',
                'meta_description' => 'Meta',
                'category_id' => $category->id,
                'author_id' => $author->id,
                'status' => 'published',
                'review_status' => 'approved',
                'is_hot' => '1',
                'is_featured' => '1',
            ])
            ->assertRedirect();

        $article = Article::query()->where('title', '推荐标记测试文章')->firstOrFail();

        $this->assertTrue((bool) $article->is_hot);
        $this->assertTrue((bool) $article->is_featured);
    }

    public function test_article_list_shows_hot_and_featured_badges(): void
    {
        $admin = Admin::query()->create([
            'username' => 'articles_badges_admin',
            'password' => 'secret-123',
            'email' => 'articles-badges@example.com',
            'display_name' => 'Articles Badges Admin',
            'role' => 'admin',
            'status' => 'active',
        ]);
        $category = Category::query()->create([
            'name' => '科技资讯',
            'slug' => 'tech',
        ]);
        $author = Author::query()->create([
            'name' => 'GEOFlow',
        ]);
        Article::query()->create([
            'title' => '后台标签展示文章',
            'slug' => 'admin-badges-article',
            'excerpt' => '摘要',
            'content' => '正文',
            'category_id' => $category->id,
            'author_id' => $author->id,
            'status' => 'published',
            'review_status' => 'approved',
            'is_hot' => true,
            'is_featured' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.articles.index'))
            ->assertOk()
            ->assertSee(__('admin.articles.badge.hot'))
            ->assertSee(__('admin.articles.badge.featured'));
    }

    public function test_article_batch_review_routes_are_relative_when_proxy_scheme_is_wrong(): void
    {
        $admin = Admin::query()->create([
            'username' => 'articles_relative_routes_admin',
            'password' => 'secret-123',
            'email' => 'articles-relative-routes@example.com',
            'display_name' => 'Articles Routes Admin',
            'role' => 'admin',
            'status' => 'active',
        ]);
        $category = Category::query()->create([
            'name' => '科技资讯',
            'slug' => 'tech-relative-routes',
        ]);
        $author = Author::query()->create([
            'name' => 'GEOFlow',
        ]);
        Article::query()->create([
            'title' => '审核相对路径测试文章',
            'slug' => 'admin-review-relative-url',
            'excerpt' => '摘要',
            'content' => '正文',
            'category_id' => $category->id,
            'author_id' => $author->id,
            'status' => 'draft',
            'review_status' => 'pending',
        ]);

        $this->actingAs($admin, 'admin')
            ->withServerVariables([
                'HTTP_HOST' => 'geo.gpt88.cc:443',
                'HTTPS' => 'off',
            ])
            ->get(route('admin.articles.index', [], false))
            ->assertOk()
            ->assertSee("window.location.protocol === 'http:'", false)
            ->assertSee("window.location.port === '443'", false)
            ->assertSee('"batch_update_review":"\/geo_admin\/articles\/batch\/update-review"', false)
            ->assertSee("actionUrl.protocol = 'https:';", false)
            ->assertSee("actionUrl.port = '';", false)
            ->assertSee('return actionUrl.pathname + actionUrl.search + actionUrl.hash;', false)
            ->assertSee("batchForm.setAttribute('action', targetAction);", false)
            ->assertDontSee('resolvedUrl.toString()', false)
            ->assertDontSee('http://geo.gpt88.cc:443/geo_admin/articles/batch/update-review', false);
    }

    public function test_batch_review_redirects_back_to_https_without_default_port(): void
    {
        config(['trustedproxy.proxies' => '*']);

        $admin = Admin::query()->create([
            'username' => 'articles_review_redirect_admin',
            'password' => 'secret-123',
            'email' => 'articles-review-redirect@example.com',
            'display_name' => 'Articles Review Redirect Admin',
            'role' => 'admin',
            'status' => 'active',
        ]);
        $category = Category::query()->create([
            'name' => '审核跳转分类',
            'slug' => 'review-redirect-category',
        ]);
        $author = Author::query()->create([
            'name' => 'GEOFlow',
        ]);
        $article = Article::query()->create([
            'title' => '审核跳转测试文章',
            'slug' => 'admin-review-redirect-url',
            'excerpt' => '摘要',
            'content' => '正文',
            'category_id' => $category->id,
            'author_id' => $author->id,
            'status' => 'draft',
            'review_status' => 'pending',
        ]);

        $this->actingAs($admin, 'admin')
            ->withHeaders([
                'referer' => 'http://geo.gpt88.cc:443/geo_admin/articles?review_status=pending',
            ])
            ->withServerVariables([
                'HTTP_HOST' => 'geo.gpt88.cc:443',
                'HTTP_X_FORWARDED_PROTO' => 'https',
                'HTTP_X_FORWARDED_PORT' => '443',
            ])
            ->post('/geo_admin/articles/batch/update-review', [
                'article_ids' => [(int) $article->id],
                'review_status' => 'approved',
            ])
            ->assertRedirect('https://geo.gpt88.cc/geo_admin/articles?review_status=pending');
    }

    public function test_admin_brand_stays_fixed_when_public_site_name_changes(): void
    {
        $admin = Admin::query()->create([
            'username' => 'admin_brand_admin',
            'password' => 'secret-123',
            'email' => 'admin-brand@example.com',
            'display_name' => 'Brand Admin',
            'role' => 'admin',
            'status' => 'active',
        ]);

        SiteSetting::query()->create([
            'setting_key' => 'site_name',
            'setting_value' => 'Public Frontend Name',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(AdminWeb::siteName())
            ->assertDontSee('Public Frontend Name');
    }
}
