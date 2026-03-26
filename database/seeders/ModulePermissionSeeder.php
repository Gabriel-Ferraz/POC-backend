<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ModulePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Admin — Users
            ['name' => 'admin.users', 'type' => 'page', 'module' => 'admin'],
            ['name' => 'admin.users.store', 'type' => 'action', 'module' => 'admin'],
            ['name' => 'admin.users.update', 'type' => 'action', 'module' => 'admin'],
            ['name' => 'admin.users.destroy', 'type' => 'action', 'module' => 'admin'],

            // Admin — Users > Roles
            ['name' => 'admin.users.roles', 'type' => 'page', 'module' => 'admin'],
            ['name' => 'admin.users.roles.update', 'type' => 'action', 'module' => 'admin'],

            // Admin — Users > Permissions
            ['name' => 'admin.users.permissions', 'type' => 'page', 'module' => 'admin'],
            ['name' => 'admin.users.permissions.update', 'type' => 'action', 'module' => 'admin'],

            // Admin — Roles
            ['name' => 'admin.roles', 'type' => 'page', 'module' => 'admin'],
            ['name' => 'admin.roles.store', 'type' => 'action', 'module' => 'admin'],
            ['name' => 'admin.roles.update', 'type' => 'action', 'module' => 'admin'],
            ['name' => 'admin.roles.destroy', 'type' => 'action', 'module' => 'admin'],

            // Admin — Roles > Permissions
            ['name' => 'admin.roles.permissions', 'type' => 'page', 'module' => 'admin'],
            ['name' => 'admin.roles.permissions.update', 'type' => 'action', 'module' => 'admin'],

            // Admin — Permissions
            ['name' => 'admin.permissions', 'type' => 'page', 'module' => 'admin'],

            // Admin — Audit Logs
            ['name' => 'admin.audit-logs', 'type' => 'page', 'module' => 'admin'],

            // Logistic — Planning
            ['name' => 'logistic.planning', 'type' => 'page', 'module' => 'logistic'],
            ['name' => 'logistic.planning.store', 'type' => 'action', 'module' => 'logistic'],
            ['name' => 'logistic.planning.update', 'type' => 'action', 'module' => 'logistic'],
            ['name' => 'logistic.planning.destroy', 'type' => 'action', 'module' => 'logistic'],

            // Logistic — Types
            ['name' => 'logistic.planning-types', 'type' => 'page', 'module' => 'logistic'],
            ['name' => 'logistic.service-types', 'type' => 'page', 'module' => 'logistic'],
            ['name' => 'logistic.attendance-types', 'type' => 'page', 'module' => 'logistic'],

            // Estoque (Sucata)
            ['name' => 'estoque.dashboard-gerencial', 'type' => 'page', 'module' => 'estoque'],
            ['name' => 'estoque.curva-abc', 'type' => 'page', 'module' => 'estoque'],
            ['name' => 'estoque.risco-ruptura', 'type' => 'page', 'module' => 'estoque'],
            ['name' => 'estoque.visao-geral', 'type' => 'page', 'module' => 'estoque'],

            // Almoxarifado (Insumos)
            ['name' => 'almoxarifado.dashboard-gerencial', 'type' => 'page', 'module' => 'almoxarifado'],

            // Manutencao
            ['name' => 'manutencao.visao-geral', 'type' => 'page', 'module' => 'manutencao'],
            ['name' => 'manutencao.equipamentos', 'type' => 'page', 'module' => 'manutencao'],
            ['name' => 'manutencao.ordens-servico', 'type' => 'page', 'module' => 'manutencao'],
            ['name' => 'manutencao.veiculos', 'type' => 'page', 'module' => 'manutencao'],
            ['name' => 'manutencao.disponibilidade', 'type' => 'page', 'module' => 'manutencao'],
            ['name' => 'manutencao.vencimento-oleo', 'type' => 'page', 'module' => 'manutencao'],
            ['name' => 'manutencao.pecas', 'type' => 'page', 'module' => 'manutencao'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['type' => $permission['type'], 'module' => $permission['module']]
            );
        }
    }
}
