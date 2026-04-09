# HAAT Backend

Laravel REST API backend for the HAAT application.

## Branch Strategy

```
main      → production  (auto-deploys on PR merge)
develop   → staging     (auto-deploys on PR merge)
feature/* → open PR to develop
```

**Never push directly to `main` or `develop`.** All changes go through pull requests.

## Contributing

### 1. Clone the repo

```bash
git clone https://github.com/BidecSolutions/haat-backend.git
cd haat-backend
```

### 2. Create a feature branch off `develop`

```bash
git checkout develop
git pull origin develop
git checkout -b feature/your-feature-name
```

Branch naming:

| Type | Pattern | Example |
|------|---------|--------|
| Feature | `feature/` | `feature/add-order-api` |
| Bug fix | `fix/` | `fix/otp-expiry-logic` |
| Hotfix (prod) | `hotfix/` | `hotfix/payment-crash` |
| Chore | `chore/` | `chore/update-deps` |

### 3. Set up local environment

```bash
cp .env.example .env
# Fill in your local DB credentials

composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

### 4. Commit your changes

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add product listing endpoint
fix: resolve null pointer in checkout
chore: update composer dependencies
docs: update API usage in README
```

### 5. Open a Pull Request to `develop`

- Describe what the PR does and why
- Link any related issues
- A team member must review and approve before merge
- CI will auto-deploy to **staging** on merge

### 6. Releasing to production

When staging is verified, open a PR from `develop` → `main`.
- Requires review and approval
- CI will auto-deploy to **production** on merge

## Deployment

| Branch | Environment | Trigger |
|--------|-------------|--------|
| `develop` | Staging | PR merged into `develop` |
| `main` | Production | PR merged into `main` |

Deployments run via GitHub Actions (`.github/workflows/`).
The server runs Docker Compose with PHP-FPM + MySQL 8.4.

## Environment Variables

See `.env.example` for all required variables. Key ones:

| Variable | Description |
|----------|-------------|
| `APP_KEY` | Laravel encryption key — generate with `php artisan key:generate` |
| `DB_*` | MySQL connection (host must remain `mysql` in Docker) |
| `MAIL_*` | Mail driver config (use `log` for local dev) |
