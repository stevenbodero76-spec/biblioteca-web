<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Loan;
use App\Policies\LoanPolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Loan::class, LoanPolicy::class);

        View::composer('layouts.app', function ($view) {
            $notificacionesEspera = collect();

            if (Auth::check() && Auth::user()->reader) {
                $notificacionesEspera = Auth::user()->reader
                    ->waitlists()
                    ->with('book')
                    ->where('estado', 'notificado')
                    ->latest('notificado_at')
                    ->get();
            }

            $navCategories = Cache::remember('nav_categories', 300, fn () => Category::orderBy('id')->get());

            $view->with(compact('notificacionesEspera', 'navCategories'));
        });
    }
}
