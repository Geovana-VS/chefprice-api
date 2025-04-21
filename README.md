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