# 🤖 Copilot Instructions for GeoBus-TCC

## Visão Geral da Arquitetura
- Projeto Laravel 12 com painel administrativo Filament 3.x e controle de acesso baseado em Spatie Laravel Permission 6.x.
- Estrutura principal:
  - `app/Models/`: Modelos de domínio (ex: `User`, `Role`, `Permission`, `PontosDeParada`).
  - `app/Filament/Resources/`: Recursos Filament para CRUD e telas administrativas.
  - `app/Services/`: Serviços de domínio e integrações externas (ex: Google).
  - `app/Policies/`: Políticas de autorização customizadas.
  - `routes/web.php`: Rotas web e administrativas.
  - `config/permission.php`: Configurações do Spatie Permission.
- Fluxo de autenticação pode envolver login social via Google (ver `.env` e `filament-socialite.php`).

## Workflows Essenciais
- **Instalação:**
  - `composer install` para dependências PHP.
  - Copie `.env.example` para `.env` e configure variáveis.
  - `php artisan key:generate` para gerar chave da aplicação.
  - `php artisan migrate:refresh --seed` para migrar e popular o banco (cria admin padrão).
- **Execução:**
  - `php artisan serve` para rodar localmente.
- **Testes:**
  - Testes ficam em `tests/Feature/` e `tests/Unit/`.
  - Use `php artisan test` para rodar todos os testes.

## Padrões e Convenções Específicas
- **Recursos Filament:**
  - CRUDs administrativos seguem padrão de `Resource` em `app/Filament/Resources/`.
  - Use `php artisan make:filament-resource Nome` para gerar recursos.
  - Para CRUD simplificado, use `--simple` (ex: `php artisan make:filament-resource Customer --simple`).
- **Permissões:**
  - Papéis e permissões são gerenciados via Spatie Permission.
  - Políticas customizadas em `app/Policies/`.
- **Login Social:**
  - Integração via Filament Socialite (Google). Configure variáveis `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` no `.env`.
- **Seeders:**
  - Usuário admin padrão: `admin@admin.com` / senha `123456`.

## Integrações e Pontos de Atenção
- **Google OAuth:**
  - URLs de callback devem estar corretas no Google Cloud Console e `.env`.
- **Imagens e Telas:**
  - Exemplos de telas em `public/images/`.
- **Configuração PHP:**
  - Extensões obrigatórias: `pdo_mysql`, `mbstring`, `xml`, `curl`, `gd`, `zip`, `fileinfo`, `openssl`.
  - Ajuste `php.ini` conforme necessário.

## Exemplos de Arquivos-Chave
- `app/Models/User.php`: Modelo de usuário com relação a papéis/permissões.
- `app/Filament/Resources/UserResource.php`: CRUD administrativo de usuários.
- `app/Services/UserService.php`: Lógica de domínio relacionada a usuários.
- `app/Policies/UserPolicy.php`: Políticas de autorização customizadas.
- `config/permission.php`: Configuração do Spatie Permission.

## Dicas para Agentes AI
- Sempre consulte as políticas em `app/Policies/` ao implementar regras de acesso.
- Siga o padrão de geração de recursos Filament para CRUDs administrativos.
- Use os seeders para garantir ambiente de desenvolvimento consistente.
- Consulte o README.md para instruções detalhadas de setup e fluxos de autenticação.
