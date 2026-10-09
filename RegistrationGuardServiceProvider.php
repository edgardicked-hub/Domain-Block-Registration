<?php

namespace AachenerRegistrationGuard\Providers;

use AachenerRegistrationGuard\Middlewares\RegistrationDomainMiddleware;
use Plenty\Plugin\ServiceProvider;

class RegistrationGuardServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->addGlobalMiddleware(RegistrationDomainMiddleware::class);
    }
}
