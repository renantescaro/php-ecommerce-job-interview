# 🛡️ Portal Administrativo: Backend em PHP Puro

## 🎯 Objetivo do Projeto

Este projeto consiste na criação de um **Portal Administrativo** para gestão de clientes, desenvolvido em **PHP puro** (sem frameworks) e utilizando **MySQL** para persistência de dados.
A arquitetura segue o padrão RESTful API, aplicando princípios de **SOLID** e **Camadas Desacopladas** para garantir modularidade e escalabilidade.

---

## ⚙️ Requisitos Técnicos

| Requisito | Detalhe | Status |
| :--- | :--- | :--- |
| **Linguagem** | PHP Puro (Sem Frameworks) | ✅ |
| **Banco de Dados** | MySQL (PDO) | ✅ |
| **Arquitetura** | Camadas Desacopladas (Controller, Service, Repository, Model) | ✅ |
| **CRUD** | Gerenciamento completo de Clientes (com 1:N Endereços) | ✅ |
| **Bônus** | SOLID, Testes Automatizados, Escalabilidade | ✅ |

---

## 🚀 Guia de Inicialização Local

Siga os passos abaixo para preparar e subir a aplicação em seu ambiente de desenvolvimento:

### 1. Pré-requisitos

* **PHP 8.1+** (com extensões **pdo_mysql** habilitada).
* **Composer** (Gerenciador de Dependências).
* **MySQL Server** (ou MariaDB) rodando localmente.

### 2. Configuração do Ambiente e Instalação

1.  **Instalar Dependências:** Na raiz do projeto, execute:
    <pre>composer install</pre>

2.  **Configurar o Banco de Dados (DB):**
    * Crie dois bancos de dados no seu MySQL: um para desenvolvimento (ex: **database_admin**) e um para testes (ex: **database_admin_test**).
    * Execute o script SQL de criação das tabelas (.sql na raiz do projeto) em **ambos** os bancos de dados.

3.  **Configurar Variáveis de Ambiente:**
    * Crie um arquivo chamado **.env** na raiz do projeto (este arquivo está no **.gitignore** e não deve ser commitado).
    * Preencha com suas credenciais:

    <pre>
    #Configurações de Conexão
    DB_HOST=localhost
    DB_NAME=database_admin
    DB_USER=root
    DB_PASS=sua_senha
    <br>
    #Configurações de TESTE (usado pelo PHPUnit)
    TEST_DB_HOST=localhost
    TEST_DB_NAME=database_admin_test
    TEST_DB_USER=root
    TEST_DB_PASS=sua_senha
    </pre>

### 3. Execução da Aplicação (API)

Para subir a aplicação usando o servidor web interno do PHP (recomendado para desenvolvimento):

<pre>php -S localhost:8000 -t public/</pre>

**Endpoint de Exemplo (CRUD Clientes):** http://localhost:8000/api/customers

---

## 🧪 Testes Automatizados (PHPUnit)

O projeto utiliza o PHPUnit para Testes Unitários e Testes de Integração (na camada Repository).

### 1. Preparação do Driver de Cobertura (Xdebug)

Para que o PHPUnit gere o relatório de cobertura, o driver **Xdebug** deve estar ativo no modo **coverage** na sua CLI (Linha de Comando).

1.  **Descubra seu php.ini:** Execute **php --ini** e encontre o **Loaded Configuration File**.
2.  **Edite o php.ini** (no final do arquivo):
    <pre>
    [XDebug]
    zend_extension=caminho/para/php_xdebug.dll
    xdebug.mode = coverage, debug
    </pre>

### 2. Comandos de Teste

| Objetivo | Comando | Observações |
| :--- | :--- | :--- |
| **Executar Testes Unitários** | **vendor\bin\phpunit** | Roda todos os testes nas classes que herdam de **Tests\DatabaseTestCase**. |
| **Gerar Relatório de Cobertura** | **vendor\bin\phpunit --coverage-html reports/coverage** | Gera o relatório visual em **reports/coverage/index.html** |
