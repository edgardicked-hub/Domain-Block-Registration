<?php

namespace AachenerRegistrationGuard\Services;

class RegistrationRequest
{
    /** Target only contact-creation POSTs, never login, checkout or contact updates. */
    public function isRegistration(string $method, string $uri): bool
    {
        if (strtoupper($method) !== 'POST') {
            return false;
        }

        $path = explode('?', $uri, 2)[0];
        $path = rawurldecode($path);
        $segments = [];
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($segments);
            } else {
                $segments[] = strtolower($part);
            }
        }
        $path = implode('/', $segments);

        return (bool) preg_match('/\A(?:rest\/(?:v[0-9]+\/)?)?(?:io|b2b)\/customer\z/D', $path);
    }
}
