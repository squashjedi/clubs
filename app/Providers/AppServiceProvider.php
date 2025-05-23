<?php

namespace App\Providers;

use Illuminate\View\View;
use Illuminate\Support\Facades;
use App\InvitationCodeGenerator;
use App\RandomInvitationCodeGenerator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(InvitationCodeGenerator::class, RandomInvitationCodeGenerator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'club' => 'App\Models\Club',
            'sport' => 'App\Models\Sport',
        ]);

        Facades\View::composer('components.layouts.club.admin.app', function (View $view) {
            $view->with('club', request('club'));
        });

        Facades\View::composer('components.layouts.club.front.app', function (View $view) {
            $view->with('club', request('club'));
        });
    }
}
