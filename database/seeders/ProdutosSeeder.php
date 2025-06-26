<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Produto;
use App\Models\Categoria;

class ProdutosSeeder extends Seeder
{
    public function run(): void
    {
        Produto::firstOrCreate([
            'codigo_barra' => '07898962794210',
            'nome' => 'Papel Higiênico Max Pure',
            'id_categoria' => Categoria::where('nome', 'Higiene pessoal')->first()->id,
            'quantidade' => 1,
            'unidade_medida' => 'pct',
            'descricao' => 'Papel Higiênico Max Pure Folha Dupla 40 rolos 30M',
            'preco_padrao' => 29.90,
        ]);
        Produto::firstOrCreate([
            'codigo_barra' => '07891024134610',
            'nome' => 'Creme Dental Colgate',
            'id_categoria' => Categoria::where('nome', 'Higiene pessoal')->first()->id,
            'quantidade' => 1,
            'unidade_medida' => 'un',
            'descricao' => 'Creme Dental Colgate Máxima Proteção Anticáries 180G',
            'preco_padrao' => 5.99,
        ]);
        Produto::firstOrCreate([
            'codigo_barra' => '07891164166540',
            'nome' => 'Linguiça Calabresa Alegra',
            'id_categoria' => Categoria::where('nome', 'Carne suína')->first()->id,
            'quantidade' => 2500,
            'unidade_medida' => 'g',
            'descricao' => 'Linguiça Calabresa Alegra 2,5kg',
            'preco_padrao' => 29.90,
        ]);
        Produto::firstOrCreate([
            'codigo_barra' => '07894904204489',
            'nome' => 'Seleta Mista Seara',
            'id_categoria' => Categoria::where('nome', 'Legumes')->first()->id,
            'quantidade' => 1050,
            'unidade_medida' => 'g',
            'descricao' => 'Seleta Mista Seara Nature 1,05kg',
            'preco_padrao' => 19.90,
        ]);
        Produto::firstOrCreate([
            'codigo_barra' => '07897153001335',
            'nome' => 'Manteiga Frizzo',
            'id_categoria' => Categoria::where('nome', 'Laticínios')->first()->id,
            'quantidade' => 200,
            'unidade_medida' => 'g',
            'descricao' => 'Manteira Frizzo Com Sal 200g',
            'preco_padrao' => 6.99,
        ]);
        Produto::firstOrCreate([
            'codigo_barra' => '07891350034646',
            'nome' => 'Desodorante Aerosol Monange Hidratação Intensiva',
            'id_categoria' => Categoria::where('nome', 'Higiene pessoal')->first()->id,
            'quantidade' => 1,
            'unidade_medida' => 'un',
            'descricao' => 'Desodorante Aerosol Monange Hidratação Intensiva Extrato de Oliva 150ml',
        ]);

        Produto::firstOrCreate([
            'codigo_barra' => '07896227620014',
            'nome' => 'Hastes Flexíveis Cottonbaby',
            'id_categoria' => Categoria::where('nome', 'Higiene pessoal')->first()->id,
            'quantidade' => 1,
            'unidade_medida' => 'pct',
            'descricao' => 'Hastes Flexíveis Cottonbaby com 75 Unidades',
        ]);

        Produto::firstOrCreate([
            'codigo_barra' => '07891150059900',
            'nome' => 'Sabonete em Barra Lux Botanicals Orquídea Negra',
            'id_categoria' => Categoria::where('nome', 'Higiene pessoal')->first()->id,
            'quantidade' => 1,
            'unidade_medida' => 'un',
            'descricao' => 'Sabonete em Barra Lux Botanicals Orquídea Negra 85g',
        ]);

        Produto::firstOrCreate([
            'codigo_barra' => '07891010256777',
            'nome' => 'Enxaguante Bucal Listerine Cool Mint Refrescância Suave',
            'id_categoria' => Categoria::where('nome', 'Higiene pessoal')->first()->id,
            'quantidade' => 1,
            'unidade_medida' => 'un',
            'descricao' => 'Enxaguante Bucal Listerine Cool Mint Refrescância Suave sem Álcool 1L',
        ]);

        Produto::firstOrCreate([
            'codigo_barra' => '07896110010748',
            'nome' => 'Absorvente Sym Noite e Dia Suave com Abas',
            'id_categoria' => Categoria::where('nome', 'Higiene pessoal')->first()->id,
            'quantidade' => 1,
            'unidade_medida' => 'pct',
            'descricao' => 'Absorvente Higiênico Externo Sym Noite e Dia Suave com Abas 30 Unidades',
        ]);

        Produto::firstOrCreate([
            'codigo_barra' => '07896235353409',
            'nome' => 'Condicionador Monange Lisos, Te Quero!',
            'id_categoria' => Categoria::where('nome', 'Higiene pessoal')->first()->id,
            'quantidade' => 1,
            'unidade_medida' => 'un',
            'descricao' => 'Condicionador Monange Lisos, Te Quero! 325ml',
        ]);

        Produto::firstOrCreate([
            'codigo_barra' => '07891164004842',
            'nome' => 'Linguiça Toscana Aurora',
            'id_categoria' => Categoria::where('nome', 'Carne suína')->first()->id,
            'quantidade' => 700,
            'unidade_medida' => 'g',
            'descricao' => 'Linguiça Toscana Aurora 700g',
            'preco_padrao' => 19.90,
        ]);
        Produto::firstOrCreate([
            'codigo_barra' => '07506306214989',
            'nome' => 'Antitranspirante Rexona Feminino Aerosol Clinical Classic',
            'id_categoria' => Categoria::where('nome', 'Higiene pessoal')->first()->id,
            'quantidade' => 1,
            'unidade_medida' => 'un',
            'descricao' => 'Antitranspirante Rexona Feminino Aerosol Clinical Classic 150ml',
        ]);

        Produto::firstOrCreate([
            'codigo_barra' => '07896096500110',
            'nome' => 'Pão para Hambúrguer – Nino',
            'id_categoria' => Categoria::where('nome', 'Pães e massas')->first()->id,
            'quantidade' => 4,
            'unidade_medida' => 'un',
            'descricao' => 'Pão de Hambúrguer Nino 200g',
            'preco_padrao' => 11.99,
        ]);

        Produto::firstOrCreate([
            'codigo_barra' => '07893000084315',
            'nome' => 'Queijo Mussarela Sadia Soltíssimo',
            'id_categoria' => Categoria::where('nome', 'Laticínios')->first()->id,
            'quantidade' => 200,
            'unidade_medida' => 'g',
            'descricao' => 'Queijo Mussarela Sadia Soltíssimo 500g',
            'preco_padrao' => 13.90,
        ]);

        Produto::firstOrCreate([
            'codigo_barra' => '07894000050720',
            'nome' => 'Maionese Light Hellmanns',
            'id_categoria' => Categoria::where('nome', 'Condimentos e temperos')->first()->id,
            'quantidade' => 500,
            'unidade_medida' => 'g',
            'descricao' => 'Maionese Light Hellmanns 500g',
            'preco_padrao' => 9.90,
        ]);

        Produto::firstOrCreate([
            'codigo_barra' => '07894904002450',
            'nome' => 'Hambúrguer Texas Burguer Seara',
            'id_categoria' => Categoria::where('nome', 'Carne bovina')->first()->id,
            'quantidade' => 12,
            'unidade_medida' => 'un',
            'descricao' => 'Hambúrguer Texas Burguer Seara 672g',
            'preco_padrao' => 24.90,
        ]);

    }
}
