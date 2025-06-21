<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $ListaCompraStatusSeeder = new ListaCompraStatusSeeder();
        $ListaCompraStatusSeeder->run();

        $TipoImagemSeeder = new TipoImagemSeeder();
        $TipoImagemSeeder->run();

        $CategoriasSeeder = new CategoriasSeeder();
        $CategoriasSeeder->run();

        $ProdutosSeeder = new ProdutosSeeder();
        $ProdutosSeeder->run();

        $UsuariosSeeder = new UsuariosSeeder();
        $UsuariosSeeder->run();

        $ReceitasTagsSeeder = new ReceitasTagsSeeder();
        $ReceitasTagsSeeder->run();
    }
}
