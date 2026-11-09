# LabStock: backend

API em Laravel 12 do sistema LabStock.

A instalação, as regras do sistema e os passos para produção estão no [README principal](../README.md).

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Testes: `php artisan test`
