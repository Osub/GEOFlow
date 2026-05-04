<?php

namespace Tests\Feature;

use Tests\TestCase;

class TrustedProxyConfigurationTest extends TestCase
{
    public function test_admin_login_urls_respect_forwarded_prefix_from_trusted_proxy(): void
    {
        config(['trustedproxy.proxies' => '*']);

        $loginPath = '/'.ltrim((string) app('router')->getRoutes()->getByName('admin.login')?->uri(), '/');
        $expectedLoginUrl = 'https://geo.example.com/docs'.$loginPath;

        $this->get($loginPath, [
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_HOST' => 'geo.example.com',
            'HTTP_X_FORWARDED_PREFIX' => '/docs',
        ])
            ->assertOk()
            ->assertSee('action="'.$expectedLoginUrl.'"', false)
            ->assertSee('src="https://geo.example.com/docs/js/tailwindcss.play-cdn.js"', false);
    }

    public function test_https_default_port_is_removed_from_generated_admin_urls(): void
    {
        config(['trustedproxy.proxies' => '*']);

        $loginPath = '/'.ltrim((string) app('router')->getRoutes()->getByName('admin.login')?->uri(), '/');

        $this->get($loginPath, [
            'HTTP_HOST' => 'geo.gpt88.cc:443',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT' => '443',
        ])
            ->assertOk()
            ->assertSee('action="https://geo.gpt88.cc'.$loginPath.'"', false)
            ->assertSee('src="https://geo.gpt88.cc/js/tailwindcss.play-cdn.js"', false)
            ->assertSee('value="https://geo.gpt88.cc/geo_admin/locale/zh_CN"', false)
            ->assertDontSee('http://geo.gpt88.cc:443', false)
            ->assertDontSee('https://geo.gpt88.cc:443', false);
    }

    public function test_cloudflare_https_signal_removes_default_port_even_without_trusted_proxy_config(): void
    {
        config(['trustedproxy.proxies' => null]);

        $loginPath = '/'.ltrim((string) app('router')->getRoutes()->getByName('admin.login')?->uri(), '/');

        $this->get($loginPath, [
            'HTTP_HOST' => 'geo.gpt88.cc:443',
            'HTTP_CF_VISITOR' => '{"scheme":"https"}',
        ])
            ->assertOk()
            ->assertSee('action="https://geo.gpt88.cc'.$loginPath.'"', false)
            ->assertDontSee('http://geo.gpt88.cc:443', false)
            ->assertDontSee('https://geo.gpt88.cc:443', false);
    }

    public function test_admin_redirects_remove_https_default_port(): void
    {
        config(['trustedproxy.proxies' => '*']);

        $this->get('/geo_admin/articles', [
            'HTTP_HOST' => 'geo.gpt88.cc:443',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT' => '443',
        ])
            ->assertRedirect('https://geo.gpt88.cc/geo_admin/login');
    }
}
