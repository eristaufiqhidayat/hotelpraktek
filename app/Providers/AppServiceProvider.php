<?php

namespace App\Providers;

use App\Services\Hotel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(Hotel::class);
    }

    public function boot(): void
    {
        Carbon::setLocale('id');

        View::composer('partials.modal', \App\View\ModalComposer::class);
        View::composer(['layouts.app', 'auth.login', 'frontoffice.print'], function ($view) {
            $view->with('hotel', app(Hotel::class));
        });
    }
}
