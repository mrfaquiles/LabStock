# LabStock

Sistema web de controle de estoque do laboratório de química: reagentes (por lote e validade), vidrarias, equipamentos (por local e patrimônio), compras por licitação, produtos controlados e relatórios.

Trabalho de Conclusão de Curso de Tecnologia em Gestão da Tecnologia da Informação do IF Sudeste MG, Campus Muriaé.

- **Autores:** Mayara Freitas Aquiles e Pedro Henrique Rodrigues Fortini
- **Orientação:** Prof. Me. Diego Rossi

## Tecnologias

| Parte | Tecnologias |
|---|---|
| Backend (`backend/`) | PHP 8.2, Laravel 12, Sanctum (login por token) |
| Frontend (`frontend/`) | Vue 3, Vuetify, Pinia, Vue Router, Vite, jsPDF |
| Banco | MySQL ou MariaDB |

## Instalar para desenvolvimento

Você precisa de PHP 8.2 ou mais novo, Composer, Node.js 20 ou mais novo e MySQL ou MariaDB (o XAMPP já traz PHP e MariaDB).

### 1. Banco de dados

Crie um banco vazio:

```sql
CREATE DATABASE controle_laboratorio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Confira no `.env` os dados do banco (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) e defina a senha do primeiro administrador em `ADMIN_EMAIL` e `ADMIN_PASSWORD`. Depois:

```bash
php artisan migrate --seed
php artisan serve
```

A API fica em `http://127.0.0.1:8000/api/`.

### 3. Frontend

Em outro terminal:

```bash
cd frontend
npm install
npm run dev
```

O sistema abre em `http://localhost:5173`.

### 4. Primeiro acesso

1. Entre com o administrador definido no `.env`.
2. Em **Configurações**, cadastre as unidades (campus) e os laboratórios. Sem laboratório não é possível registrar lote de reagente.
3. Em **Usuários**, crie as contas da equipe.

### Dados de demonstração (opcional)

Para apresentar o sistema com dados fictícios:

```bash
php artisan db:seed --class=DemoSeeder
```

**Não use em produção:** isso cria usuários de teste com senha conhecida e registros fictícios, inclusive de produtos controlados.

## Testes

```bash
cd backend
php artisan test
```

Os testes usam SQLite em memória e não tocam no banco de desenvolvimento.

## Perfis de acesso

| Perfil | O que pode fazer |
|---|---|
| **Administrador** | Tudo, incluindo usuários, configurações, unidades, laboratórios, unidades de medida, aprovação de baixas de vidraria, estornos e correção de movimentações |
| **Técnico** | Cadastrar itens e registrar entradas, usos, baixas, transferências e compras |
| **Consulta** | Ver as telas, emitir relatórios e solicitar baixa de vidraria |

## Regras importantes

- **Estoque só muda por movimentação.** A quantidade de um item nunca é editada direto: ela vem de entradas, usos, baixas e transferências.
- **Tudo é guardado na unidade base** (kg, L ou un), com precisão de 1 mg ou 1 µL. A tela aceita g, mg, mL e µL e converte. A unidade de um reagente não muda depois que ele tem lotes.
- **Lote e validade pertencem ao lote,** não ao cadastro do reagente. O uso desconta sempre de um lote escolhido.
- **Produto controlado** exige justificativa de uso (a classificação é opcional), e seus registros não podem ser apagados nem alterados.
- **Baixa de vidraria** precisa da aprovação do administrador (pode ser desligada em Configurações).
- **Equipamento** tem um número de patrimônio por unidade. Nas transferências e baixas, escolhem-se os patrimônios que saem do local.
- **Nome e CATMAT não se repetem** dentro de cada cadastro.

## Colocar em produção

1. No `backend/.env`:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://labstock.seucampus.edu.br
   CORS_ALLOWED_ORIGINS=https://labstock.seucampus.edu.br
   ADMIN_PASSWORD=(uma senha forte, só sua)
   ```
2. Instale e prepare o backend:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --seed --force
   php artisan config:cache
   php artisan route:cache
   ```
3. Gere o frontend apontando para a API e publique a pasta `frontend/dist`. O servidor precisa devolver `index.html` para qualquer rota da aplicação.
   ```bash
   VITE_API_URL=https://labstock.seucampus.edu.br/api/ npm run build
   ```
4. Sirva o backend pela pasta `backend/public`, com Apache ou Nginx e **HTTPS**. O `php artisan serve` é só para desenvolvimento. Ligue o OPcache do PHP: sem ele cada requisição fica bem mais lenta.
5. Agende o Laravel para rodar a cada minuto. É isso que apaga os logins vencidos:
   ```
   * * * * * cd /caminho/backend && php artisan schedule:run >> /dev/null 2>&1
   ```
6. Faça **backup automático** do banco. O histórico de produtos controlados precisa ser preservado.
7. Comece com o banco vazio: não rode o `DemoSeeder`.

## Estrutura

```
backend/
  app/Http/Controllers/Api/   Regras da API (MovimentacaoEstoqueController é a base das movimentações)
  app/Models/                 Modelos; Concerns/RegistraHistorico grava quem alterou o quê
  app/Services/               Previsão de compras
  database/migrations/        Estrutura do banco
  database/seeders/           Dados iniciais e de demonstração
  routes/api.php              Rotas e permissões por perfil
  tests/Feature/              Testes automáticos
frontend/
  src/views/                  Telas
  src/components/             Componentes reutilizados
  src/stores/                 Login e configurações
  src/utils/                  Conversão de unidades e exportação de PDF/CSV
```
