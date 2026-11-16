<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Loan;
use App\Models\Order;
use App\Policies\LoanPolicy;
use App\Policies\OrderPolicy;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
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
        Gate::policy(Order::class, OrderPolicy::class);

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

            $navCategories = Category::orderBy('id')->get();
            $cartCount = app(CartService::class)->count();

            $view->with(compact('notificacionesEspera', 'navCategories', 'cartCount'));
        });
    }
}
