<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.insted');

        // Admin tem acesso irrestrito a qualquer habilidade.
        Gate::before(fn (User $user) => $user->is_admin ? true : null);

        // Cada permissão do catálogo vira uma habilidade (Gate) verificável
        // via middleware `can:`, @can nas views e $user->can(...).
        foreach (Permissions::todas() as $chave) {
            Gate::define($chave, fn (User $user) => $user->temPermissao($chave));
        }
    }
}
