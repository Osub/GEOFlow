<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class NormalizeProxyHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldNormalizeHttps($request)) {
            $host = $request->getHost();

            if ($host !== '') {
                URL::forceScheme('https');
                URL::forceRootUrl($this->rootUrl($request, $host));

                $request->server->set('HTTPS', 'on');
                $request->server->set('SERVER_PORT', '443');
                $request->server->set('HTTP_HOST', $host);
                $request->headers->set('host', $host);

                $this->normalizeRequestUrlHeader($request, 'referer', $host);
                $this->normalizeRequestUrlHeader($request, 'origin', $host);
            }
        }

        return $next($request);
    }

    private function shouldNormalizeHttps(Request $request): bool
    {
        return $this->hasForwardedHttpsSignal($request)
            || ($request->isSecure() && ($this->usesDefaultHttpsPort($request) || $this->configuredAppUrlUsesHttpsDefaultPort()));
    }

    private function hasForwardedHttpsSignal(Request $request): bool
    {
        $forwardedProto = strtolower(trim(explode(',', (string) $request->headers->get('x-forwarded-proto'))[0] ?? ''));
        $forwardedSsl = strtolower((string) $request->headers->get('x-forwarded-ssl'));

        return $forwardedProto === 'https'
            || $forwardedSsl === 'on'
            || $this->cloudflareVisitorScheme($request) === 'https'
            || (string) $request->headers->get('x-forwarded-port') === '443'
            || (string) $request->server->get('SERVER_PORT') === '443'
            || str_ends_with((string) $request->headers->get('host'), ':443');
    }

    private function usesDefaultHttpsPort(Request $request): bool
    {
        return $request->getPort() === 443
            || (string) $request->headers->get('x-forwarded-port') === '443'
            || (string) $request->server->get('SERVER_PORT') === '443'
            || str_ends_with((string) $request->headers->get('host'), ':443');
    }

    private function configuredAppUrlUsesHttpsDefaultPort(): bool
    {
        $appUrl = (string) config('app.url', '');

        return strtolower((string) parse_url($appUrl, PHP_URL_SCHEME)) === 'https'
            && (int) parse_url($appUrl, PHP_URL_PORT) === 443;
    }

    private function normalizeRequestUrlHeader(Request $request, string $header, string $host): void
    {
        $value = (string) $request->headers->get($header, '');
        if ($value === '') {
            return;
        }

        $normalized = preg_replace(
            '#^https?://'.preg_quote($host, '#').':443(?=/|$)#i',
            'https://'.$host,
            $value
        );

        if (! is_string($normalized) || $normalized === $value) {
            return;
        }

        $request->headers->set($header, $normalized);
        $request->server->set('HTTP_'.strtoupper(str_replace('-', '_', $header)), $normalized);
    }

    private function rootUrl(Request $request, string $host): string
    {
        $prefix = trim((string) $request->headers->get('x-forwarded-prefix'), '/');

        return 'https://'.$host.($prefix !== '' ? '/'.$prefix : '');
    }

    private function cloudflareVisitorScheme(Request $request): string
    {
        $visitor = json_decode((string) $request->headers->get('cf-visitor', ''), true);

        return is_array($visitor) ? strtolower((string) ($visitor['scheme'] ?? '')) : '';
    }
}
