# Orders Platform (Laravel + Horizon)

Queued CSV import → order workflow (reserve stock → simulate payment → finalize/rollback)
with KPIs & leaderboard in Redis, refunds, notifications, and a small API + dashboard.

## Quick start
```bash
docker compose up -d
docker compose exec app bash -lc "cd src && [ -f .env ] || cp .env.example .env && composer install && php artisan key:generate && php artisan migrate"
docker compose up -d horizon

Dashboard: http://localhost:8000/dashboard
Horizon: http://localhost:8000/horizon


Example of Order.csv File 
customer_email,customer_name,order_id_external,sku,qty,price_cents,order_total_cents
new1@demo.com,New Three,EXT-3001,SKU-1,2,1000,2000
new2@demo.com,New Four,EXT-3002,SKU-3,1,700,700
