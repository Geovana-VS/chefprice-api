<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\ListaCompraStatus; // Opcional: se você criar o Model

class ListaCompraStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $status = [
            ['nome' => 'Ativa', 'descricao' => 'Lista de compras em aberto e ativa.'],
            ['nome' => 'Arquivada', 'descricao' => 'Lista de compras que não está mais ativa mas mantida para referência.'],
        ];

        // Usando Query Builder para inserção
        DB::table('lista_compra_status')->insert($status);

        // Alternativamente, se você criar o Model ListaCompraStatus:
        // foreach ($status as $s) {
        //     ListaCompraStatus::create($s);
        // }
    }
}