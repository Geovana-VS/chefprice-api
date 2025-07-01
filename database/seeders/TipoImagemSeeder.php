<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoImagem;

class TipoImagemSeeder extends Seeder
{
    public function run(): void
    {
        TipoImagem::firstOrCreate(['nome' => 'Produto']);
        TipoImagem::firstOrCreate(['nome' => 'Receita']);
        TipoImagem::firstOrCreate(['nome' => 'Cupom Fiscal Genérico']);
        TipoImagem::firstOrCreate(['nome' => 'Cupom Fiscal Receita']);
    }
}
