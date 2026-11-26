<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class SimulationDatabase
{
    public static function run(Closure $action): mixed
    {
        $originalConnection = DB::getDefaultConnection();
        $originalStore = config('permission.cache.store');
        config(['database.connections.sop_simulation' => [
            'driver' => 'sqlite', 'database' => database_path('sop-simulation.sqlite'), 'foreign_key_constraints' => true, 'busy_timeout' => 5000,
            'transaction_mode' => 'IMMEDIATE',
        ], 'permission.cache.store' => 'array']);
        DB::setDefaultConnection('sop_simulation');
        app(PermissionRegistrar::class)->initializeCache();
        try {
            return $action();
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('sop_simulation');
            config(['permission.cache.store' => $originalStore]);
            $registrar = app(PermissionRegistrar::class);
            $registrar->clearPermissionsCollection();
            $registrar->initializeCache();
        }
    }
}
