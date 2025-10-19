<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Manager;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $adminManager = Manager::where('email', 'admin@example.com')->first();

        Employee::factory()->forManager($adminManager)->create([
            'name' => 'João Silva',
            'email' => 'joao.silva@example.com',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);

        Employee::factory()->forManager($adminManager)->create([
            'name' => 'Maria Santos',
            'email' => 'maria.santos@example.com',
            'city' => 'Rio de Janeiro',
            'state' => 'RJ',
        ]);

        Employee::factory()->forManager($adminManager)->create([
            'name' => 'Pedro Oliveira',
            'email' => 'pedro.oliveira@example.com',
            'city' => 'Belo Horizonte',
            'state' => 'MG',
        ]);
    }
}
