<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UsuariosSeeder extends Seeder
{
    public function run(): void
    {
        $adm = User::where('email', 'tsdalessandro@gmail.com');

        if ($adm->exists()) {
            return; // Usuário já existe, não faz nada
        }
        
        User::firstOrCreate([
            'name' => 'Thiago',
            'email' => 'tsdalessandro@gmail.com',
            'password' => bcrypt('12345678'),
            'is_admin' => true,
        ]);

        User::factory(10)->create()->each(function ($user) {
        });
    }
}
