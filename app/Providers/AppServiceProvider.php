<?php

namespace App\Providers;

use App\Models\Club;
use Illuminate\View\View;
use Illuminate\View\Factory;
use Illuminate\Support\Facades;
use App\InvitationCodeGenerator;
use Illuminate\Support\Facades\Route;
use App\RandomInvitationCodeGenerator;
use Illuminate\Database\Eloquent\Model;
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
    public function boot(Factory $view): void
    {
        Model::automaticallyEagerLoadRelationships();

        Route::model('club', Club::class);

        Facades\View::composer('layouts.club-admin', function (View $view) {
            $view->with('club', request('club'));
        });

        Facades\View::composer('layouts.club-front', function (View $view) {
            $view->with('club', request('club'));
        });
    }
}
