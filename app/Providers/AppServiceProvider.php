<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Loan;
use App\Policies\LoanPolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

            $navCategoriesRaw = Cache::remember(
                'nav_categories_v2',
                300,
                fn () => Category::orderBy('id')->get(['id', 'nombre', 'icono'])->toArray()
            );
            $navCategories = is_array($navCategoriesRaw)
                ? Category::hydrate($navCategoriesRaw)
                : Category::orderBy('id')->get();

            $adminSidebarStats = null;
            if (Auth::check() && Auth::user()->esAdmin()) {
                $row = DB::selectOne("
                    SELECT
                        (SELECT COUNT(*) FROM books) AS libros,
                        (SELECT COUNT(*) FROM categories) AS categorias,
                        (SELECT COUNT(*) FROM loans WHERE estado IN ('prestado', 'vencido')) AS prestamos_activos,
                        (SELECT COUNT(*) FROM readers) AS lectores,
                        (SELECT COUNT(*) FROM waitlists WHERE estado IN ('en_espera', 'notificado')) AS espera,
                        (SELECT COUNT(*) FROM complaints WHERE estado != 'resuelto') AS reclamos,
                        (SELECT COUNT(*) FROM reviews) AS resenas
                ");

                $adminSidebarStats = [
                    'libros' => (int) ($row->libros ?? 0),
                    'categorias' => (int) ($row->categorias ?? 0),
                    'prestamos_activos' => (int) ($row->prestamos_activos ?? 0),
                    'lectores' => (int) ($row->lectores ?? 0),
                    'espera' => (int) ($row->espera ?? 0),
                    'reclamos' => (int) ($row->reclamos ?? 0),
                    'resenas' => (int) ($row->resenas ?? 0),
                ];
            }

            $view->with(compact('notificacionesEspera', 'navCategories', 'adminSidebarStats'));
        });
    }
}
