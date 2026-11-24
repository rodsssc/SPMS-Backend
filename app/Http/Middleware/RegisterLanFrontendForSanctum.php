<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegisterLanFrontendForSanctum
{
    public function handle(Request $request, Closure $next): Response
    {
        $frontendUrl = $request->headers->get('referer') ?: $request->headers->get('origin');
        $parts = $frontendUrl ? parse_url($frontendUrl) : false;

        if (is_array($parts)) {
            $host = strtolower($parts['host'] ?? '');
            $port = $parts['port'] ?? (($parts['scheme'] ?? '') === 'https' ? 443 : 80);
            $frontendHost = $host.':'.$port;

            if ($port === 5173 && $request->getHttpHost() === $frontendHost && $this->isLocalNetworkHost($host)) {
                $stateful = config('sanctum.stateful', []);
                $stateful[] = $frontendHost;
                config(['sanctum.stateful' => array_values(array_unique($stateful))]);
            }
        }

        return $next($request);
    }

    private function isLocalNetworkHost(string $host): bool
    {
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return false;
        }

        $octets = array_map('intval', explode('.', $host));

        return $octets[0] === 10
            || ($octets[0] === 192 && $octets[1] === 168)
            || ($octets[0] === 172 && $octets[1] >= 16 && $octets[1] <= 31);
    }
}
