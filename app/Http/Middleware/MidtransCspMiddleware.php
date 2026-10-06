<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MidtransCspMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $scriptSrc = implode(' ', [
            "'self'",
            "'unsafe-inline'",
            "'unsafe-eval'",
            'https://app.sandbox.midtrans.com',
            'https://app.midtrans.com',
            'https://snap-assets.sandbox.midtrans.com',
            'https://snap-assets.midtrans.com',
            'https://api.sandbox.midtrans.com',
            'https://api.midtrans.com',
            'https://pay.google.com',
            'https://gwk.gopayapi.com',
            'https://www.googletagmanager.com',
            'https://o.alicdn.com',
            'https://g.alicdn.com',
            'https://cdn.jsdelivr.net',
            'https://fonts.googleapis.com',
            'https://www.paypal.com',
            'https://www.sandbox.paypal.com',
            'https://code.jquery.com',
            'https://cdn.datatables.net',
            'https://cdnjs.cloudflare.com',
            'https://embed.tawk.to',
        ]);

        $frameSrc = implode(' ', [
            "'self'",
            'https://app.sandbox.midtrans.com',
            'https://app.midtrans.com',
            'https://sandbox.midtrans.com',
            'https://www.youtube.com',
            'https://embed.tawk.to',
            'https://*.tawk.to',
        ]);

        $styleSrc = implode(' ', [
            "'self'",
            "'unsafe-inline'",
            'https://fonts.googleapis.com',
            'https://cdn.jsdelivr.net',
            'https://snap-assets.sandbox.midtrans.com',
            'https://snap-assets.midtrans.com',
            'https://cdn.datatables.net',
            'https://cdnjs.cloudflare.com',
        ]);

        $connectSrc = implode(' ', [
            "'self'",
            'https://api.sandbox.midtrans.com',
            'https://api.midtrans.com',
            'https://app.sandbox.midtrans.com',
            'https://app.midtrans.com',
            'https://snap-assets.sandbox.midtrans.com',
            'https://snap-assets.midtrans.com',
            'https://embed.tawk.to',
            'https://*.tawk.to',
        ]);

        $imgSrc = "'self' data: https:";

        $fontSrc = "'self' https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com";

        $csp = "default-src 'self'; "
             . "script-src {$scriptSrc}; "
             . "frame-src {$frameSrc}; "
             . "style-src {$styleSrc}; "
             . "connect-src {$connectSrc}; "
             . "img-src {$imgSrc}; "
             . "font-src {$fontSrc}";

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
