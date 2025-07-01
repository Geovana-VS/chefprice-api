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
        php artisan migrate --seed
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

![Entidade-Relacionamento](https://uml.planttext.com/plantuml/svg/hLVRRkCs47qtu7-OfWz9q0G8Yjq-B3R1kewpCPeRicvHe2Y8iJYnYKYYbgJIfaN_lKDTAScEjyq-MPeZpkLmRlvW7HgN9_5uj0-dfwSmZGbJU58snzW2fXAiCwXMQmUnIidwZyOZ-wZI30qcXT0zPmJQkBKEaIX6QoVE6RNC7SqZdNvIVn6Sm3vSAac0DGIACJiUhRqqKYQAgNgX97FFa52o95D_a13RaVms7dqZwO7DmmANCHsacD2H91iTbgR12m0jx7dX7hn_3mUBesylBixFfzD3E3lpAegWJs6MEkKKHxT4In9q2jTQKbn4o-6M1dwsP8vWB-SVktS8Vuz70Dyf2U_Wn_d7-St2lnSsty4la_lfvUJUIoX15RSYQ6JYYOnwK2G5EiRF9ulPOduzytZ6xl-XZUngKbQWJ5Jg3TxUNiqcDrvggEH7EFr8QVT4P0ZT9WDv9ZS1_tXAoc2dZA-qKSWHH_Npzh1rF-nNcBupMkPEi-6iVDfgDj9IhRHOeZ7ODTzr2Bu_EV5IdmX6HQW3w6q9AIcQCE6tYvz-xnZwd2DdY4H9xFDiEh-UN1Mnf8LC92JveQFqwt1mgQnZZwA03B6kPLjfuLXohb-Zz30InYf5Gomp56d1GRdYR2VKbe6kl3pZjCEu2t24A2ATPCNL-23h6z2fwob_1L7t517xo_oOycaxArPa-J9MKRT-NYABcq8UwvVpQ37xTL7MOIfLGcLiGIOwRcAQEIVkYlghrSWyNHbEEZP7BGEg5OgTRCZwa_-M8zloe2g5GGte9RVB9umqDzSW-22KcSCCso38lxxA_JHFo6XHA6AWFf7o8CihFyfB6wRH0bTj9Wc7go_iUD1Hjb2PdYUueeILENuHoh-bNwhCCnVUTQrgXvOTPTywaWpntCH1Bdj_19hFkNhgfopo05fhuLiGQ-g22UU_A9Q1_jmg2qztA-yrxP57i4undaGHMRlmayzto4egi12N4t4hNGNgjMrmkSLsplVUs8KVtXGZ7fSgBbUAbbRUIXnVqQQGwSzC6R93u7-HL6qrTtnzhjoEB3ne0zUOFXyxVUn_uPvYT8ethxNAkejELGo2gq5ChDMHGkvrtS8Gs001mTkBqeDyh0yCIr0qwLvR6wRzosOsv_XmkBQMwj6wmTBMHhZ3KdK55xm94wzoZp3dhcmuEUlxEBZDFFiiQ7PEgN3bD-UKIDh3pfNSLHlXFLboJRvNGgQ3fVrqxosU6tBrXVmAsFh4fSIEiZrRFlKgAb3cLsDVCSFsa_7gAjdDO0Z0cxVD6K-x2oebm-TOezT-TNlpiLC_63drfFXlGTNP2qyRlZSbzNwvp7UnyQZA0ZW-FeDcsst4pUPLu7xrtm9Lml6eoj_W--6GBi3gUIlUQ0mdO-zacNY1gLRGJf-IbqdkriKVkJON-qmCkscWkhh_a8T1CWQWDnaqdhwjpRsb6ztkRnXfFmjY7wW8i9gJRlLt6T-0jLH-u0sF_od_2m00)
