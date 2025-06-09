<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;

class CategoriasSeeder extends Seeder
{
    public function run(): void
    {
        Categoria::firstOrCreate(['nome' => 'Frutas']);
        Categoria::firstOrCreate(['nome' => 'Verduras']);
        Categoria::firstOrCreate(['nome' => 'Legumes']);
        Categoria::firstOrCreate(['nome' => 'Carne bovina']);
        Categoria::firstOrCreate(['nome' => 'Carne suína']);
        Categoria::firstOrCreate(['nome' => 'Aves']);
        Categoria::firstOrCreate(['nome' => 'Peixes']);
        Categoria::firstOrCreate(['nome' => 'Frutos do mar']);
        Categoria::firstOrCreate(['nome' => 'Laticínios']);
        Categoria::firstOrCreate(['nome' => 'Grãos e cereais']);
        Categoria::firstOrCreate(['nome' => 'Pães e massas']);
        Categoria::firstOrCreate(['nome' => 'Bebidas']);
        Categoria::firstOrCreate(['nome' => 'Ovos']);
        Categoria::firstOrCreate(['nome' => 'Condimentos e temperos']);
        Categoria::firstOrCreate(['nome' => 'Higiene pessoal']);
        Categoria::firstOrCreate(['nome' => 'Limpeza doméstica']);      
    }
}
