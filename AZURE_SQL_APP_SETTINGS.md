# Azure SQL App Settings

Add these in Azure App Service > Configuration > Application settings:

```text
DB_DRIVER=sqlsrv
DB_HOST=yuvaclub-sql-central.database.windows.net
DB_PORT=1433
DB_DATABASE=yuva_club
DB_USERNAME=yuvaadmin
DB_PASSWORD=<your SQL admin password>
APP_ENV=production
APP_URL=https://www.yuvaclub.app
MAIL_FROM_EMAIL=noreply@yuvaclub.app
MAIL_FROM_NAME=YUVA Club
ALLOWED_CORS_ORIGINS=https://www.yuvaclub.app
```

Then open `/backend-health.php`.

Expected result:

```json
{
  "ok": true,
  "database_configured": true,
  "database_connected": true,
  "database_driver": "sqlsrv"
}
```

Note: the registration submit path is database-ready for Azure SQL. The full admin approval workflow still needs the next conversion pass before file storage can be fully retired.
