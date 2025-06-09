<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReceitaTag;

class ReceitasTagsSeeder extends Seeder
{
    public function run(): void
    {
        ReceitaTag::firstOrCreate(['nome' => 'Vegetariana']);
        ReceitaTag::firstOrCreate(['nome' => 'Vegana']);
        ReceitaTag::firstOrCreate(['nome' => 'Sem Glúten']);
        ReceitaTag::firstOrCreate(['nome' => 'Sem Lactose']);
        ReceitaTag::firstOrCreate(['nome' => 'Rápida']);
        ReceitaTag::firstOrCreate(['nome' => 'Fácil']);
        ReceitaTag::firstOrCreate(['nome' => 'Saudável']);
        ReceitaTag::firstOrCreate(['nome' => 'Doces']);
        ReceitaTag::firstOrCreate(['nome' => 'Salgados']);
        ReceitaTag::firstOrCreate(['nome' => 'Entradas']);
        ReceitaTag::firstOrCreate(['nome' => 'Pratos Principais']);
        ReceitaTag::firstOrCreate(['nome' => 'Sobremesas']);
        ReceitaTag::firstOrCreate(['nome' => 'Bebidas']);
        ReceitaTag::firstOrCreate(['nome' => 'Comida de Conforto']); 
        ReceitaTag::firstOrCreate(['nome' => 'Internacional']);
        ReceitaTag::firstOrCreate(['nome' => 'Regional']);
        ReceitaTag::firstOrCreate(['nome' => 'Para Crianças']);
        ReceitaTag::firstOrCreate(['nome' => 'Para Festas']);
        ReceitaTag::firstOrCreate(['nome' => 'Para Dietas Especiais']);
        ReceitaTag::firstOrCreate(['nome' => 'Comida de Rua']);
        ReceitaTag::firstOrCreate(['nome' => 'Comida de Natal']);
        ReceitaTag::firstOrCreate(['nome' => 'Comida de Ano Novo']);
            
    }
}
