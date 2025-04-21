# API Chef Price

API backend para a aplicação Chef Price, responsável por gerenciar receitas, produtos, categorias, usuários e outras funcionalidades relacionadas. Construída com Laravel.

## Descrição

Esta API fornece endpoints RESTful para operações CRUD (Criar, Ler, Atualizar, Deletar) nos principais recursos da aplicação, além de funcionalidades de autenticação e verificação de email utilizando Laravel Sanctum.

## Pré-requisitos

Certifique-se de ter o seguinte software instalado em seu ambiente de desenvolvimento:

* **PHP:** Versão >= 8.2
* **Composer:** Gerenciador de dependências do PHP
* **Banco de Dados:** Microsoft SQL Server
    * É necessário ter o driver ODBC e a extensão PHP `pdo_sqlsrv` habilitada.

## Instalação

Siga os passos abaixo para configurar o ambiente de desenvolvimento:

1.  **Clonar o Repositório:**
    ```bash
    git clone https://github.com/Geovana-VS/chefprice-api
    cd chef-price-api
    ```

2.  **Instalar Dependências:**
    ```bash
    composer install
    ```

3.  **Configurar Arquivo de Ambiente:**
    * Copie o arquivo de exemplo:
        ```bash
        cp .env.example .env
        ```
    * Edite o arquivo `.env` e configure as variáveis de ambiente, especialmente:
        * `APP_URL`: URL base da sua API (ex: `http://localhost:8000`)
        * `DB_CONNECTION`: Defina como `sqlsrv`
        * `DB_HOST`: Endereço do seu servidor SQL Server
        * `DB_PORT`: Porta do SQL Server (normalmente 1433)
        * `DB_DATABASE`: Nome do banco de dados
        * `DB_USERNAME`: Usuário do banco de dados
        * `DB_PASSWORD`: Senha do banco de dados
        * `FRONTEND_URL`: URL base da sua aplicação frontend (ex: `http://localhost:3000`)
        * `FRONTEND_EMAIL_VERIFY_SUCCESS_PATH`: Caminho no frontend para sucesso na verificação (ex: `/auth/verified`)
        * `FRONTEND_EMAIL_VERIFY_FAILED_PATH`: Caminho no frontend para falha na verificação (ex: `/auth/verification-failed`)
        * `MAIL_*`: Configure as variáveis para envio de email (ex: Mailtrap para desenvolvimento).

4.  **Gerar Chave da Aplicação:**
    ```bash
    php artisan key:generate
    ```

5.  **Executar Migrações:**
    * Certifique-se de que seu banco de dados (`DB_DATABASE`) foi criado no SQL Server.
    * Execute as migrações para criar as tabelas:
        ```bash
        php artisan migrate
        ```

6.  **Criar Link Simbólico do Storage:** (Necessário para acessar imagens salvas no disco `public`)
    ```bash
    php artisan storage:link
    ```

## Executando a Aplicação (Desenvolvimento)

Para iniciar o servidor de desenvolvimento embutido do Laravel:

```bash
php artisan serve
```

## Banco de Dados

A estrutura do banco de dados é definida através das migrations do Laravel, localizadas no diretório `database/migrations`. O Eloquent ORM é utilizado para mapear as tabelas e definir os relacionamentos entre as entidades, conforme descrito nos Models (`app/Models`).

Abaixo está um diagrama Entidade-Relacionamento (ER) que visualiza a estrutura e os relacionamentos das tabelas:

![Entidade-Relacionamento](https://uml.planttext.com/plantuml/svg/hLTjRziu3FuU8F-1wpvinLfWRzs7OIsMfUaQtDeDdNSuu70G69jDXDcIAycz5SF--oY_EtQQNdkV8lEXI3wKI2hlhKFZiYGUZvx0uU4XJ6D21TVIPXXRG1M1TGRbSkKWbegiLngFx7UfKZIOv49tan9eurPwF1gF7aLqoK88S17JKuK9xKLamrq8OxGMl03GmeuNxi2xT_0qs7jySd9yF9tkmj6HDx6I4K4eJHXJ-K59wcuWeMH1ndgLFDf3c2addUIm5cWf0gtWJ4SKvs5od4KCtooPFTZ9-CVkxCBFyGZWcOpWBNoOVvoV1_ux3-ij_3cvc9vEBk3bmO6NKe8oxeevuap9gunygAM6k2OZBoL50Xrh7q-2MJ0_ctayPSx_QXFrRHigw0YdlvDguI-UF_TuQ0ZTaCaiZOQ0Npuj1U4fuqjj93Bhi5flfgw7g3_0_HUZeynfTfuMguskGnt9fHOBD0Rx8Gm79YDHSuA_J_xufsEHpoR2Y4H24I_wk_sjCp94pL0m-oluVwYUIkiudh35MQmgsKRsp2JZEZDIztamLXhgOwcXK0jcwlAz7DfiEZ-RVEARi_VIArnbo7NXyz05NpMxdNOO3snbmYX2dQJvWNZELTQqSaE-7f2y2mg9EN3EJB6wLwQu0MInljj96rAHJAXWigRlkCTeJZfnO_DmTKNlps-kbeQl8RkZXgfiXE8ssl3j_Elyu_dikEHVwlwtctBhHIXBfPM9Wxpnw0KtociCDRVLjIoroCqSfjZG8lzvAw5jF5IMaD4YDzIaNogUFraPIt6C_HiKuBAvHCBXygwzBc-rBNk1JFKymIKbRC_nXv3-IztLta7NN6rgYvKjZQ099EdZV8JQLYW3dpv_g08JQAuoUJrmrzkey6sAT_Ur4YuLaG_ttd2ILgJP8kRkbWVpu3RAarahZ2TXIDO6VY3wJbfA1URYOb1kJ6AEUclh8LVrNUDt6lthK_dDwxN9ZmiP5s-Ffh_m2yVnKGubWNwaqf3j0_TAM_dy-SBdvugNbeLBRU0CrSs-q_l-5oueHYUriYkPTbz4PTq8BXmniLQ74hbHTcj8O0sqKhwvVZt8szQ1VhMAkYmgR_tokDtDS2tqntBZgHhA0vusDiyjdkf364hpa-8TsiN6ep8ksDy_WlgHLelhLqIE-rVh1gWKZaTbHblw_PcJW-Lw8rvRRBVrjPr5Abgk6a7JDukyJ3Atwd7lrtZBMCrbxKvsOzYkmqYZregmPwA5LP5tRqqtBmDOG_WzFoluFzHl)