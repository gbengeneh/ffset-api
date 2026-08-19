# VPS deployment with the existing PostgreSQL container

Create a separate database and role in the running PostgreSQL service; do not reuse the ICGS database or its owner. Give the API container access to the same private Docker network and use the PostgreSQL service/container name as `DB_HOST`.

```sql
CREATE USER ffset WITH ENCRYPTED PASSWORD 'replace-with-a-long-random-password';
CREATE DATABASE ffset OWNER ffset;
```

Production environment essentials:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.example.com
FRONTEND_URL=https://store.example.com
SANCTUM_STATEFUL_DOMAINS=store.example.com
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=ffset
DB_USERNAME=ffset
DB_PASSWORD=replace-with-a-long-random-password
```

## Coolify Nixpacks deployment

Create an Application from this backend repository and select the **Nixpacks** build pack. Coolify will detect Laravel and generate the runtime image. Set the exposed port to `80`, attach the API domain, and configure all values from `deployment/api.env.example` in Coolify's Environment Variables screen.

Configure persistent storage in Coolify with destination `/app/storage/app/public`. Never mount over the whole `/app/storage` directory because application logs, framework cache directories, and generated paths must remain available inside the release. Run `php artisan storage:link` once from the Coolify terminal after the storage mount is active.

Set the post-deployment command to:

```bash
php artisan migrate --force && php artisan storage:link --force && php artisan optimize
```

Create a worker application from the same repository and commit, with no public domain, using this start command:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

Create a scheduler application from the same repository and commit, with no public domain, using this start command:

```bash
php artisan schedule:work
```

The web, worker, and scheduler should use the same environment variables and database. Only the web application needs the persistent public-upload mount. Both Next.js applications remain independent Vercel projects. Configure the Paystack webhook URL as `https://api.example.com/api/payments/webhook/paystack`.

## Production integrations

- Create Paystack live keys and register the webhook URL above. Complete a low-value live payment and refund before launch.
- In Meta WhatsApp Manager, approve utility templates for each order event. Each configured template must accept four body parameters in this order: customer name, order reference, formatted total, and order status. Add the template names and Cloud API credentials to `api.env`.
- Use `FILESYSTEM_DISK=s3` plus the `AWS_*` variables for Cloudflare R2/S3, or retain the `api_storage` volume and include it in off-server backups.
- Schedule `backup-postgres.sh` daily from the VPS host, copy backups off-server, and perform a test restore with `restore-postgres.sh` before launch.
- Monitor `https://api.example.com/api/health`, the store homepage, queue failures, Docker restarts, disk use, and certificate expiry.

## Release order

1. Back up the existing PostgreSQL database.
2. Build images and run the full test suite.
3. Let the Coolify post-deployment command run the migrations and optimization.
4. Deploy or restart the web, queue worker, and scheduler applications.
5. Verify `/api/health` and the queue and scheduler logs.
6. Test category filters, a fashion variant order, an auto deposit, delivery pricing, Paystack callback/webhook, admin fulfilment, WhatsApp/email, cancellation, and refund.
7. Keep the previous images tagged until the observation window is complete.
