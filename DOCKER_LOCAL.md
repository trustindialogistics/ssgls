# Local Docker Run

This file runs the current InvoiceShelf app as a production-style Docker container using the local SQLite database.

## Start

```powershell
docker compose -f docker-compose.local.yml up --build
```

Then open:

```text
http://127.0.0.1:8090
```

## Stop

```powershell
docker compose -f docker-compose.local.yml down
```

## What Is Persisted

- `database/database.sqlite` is mounted into the container as the app database.
- `storage/app/public` is mounted for uploaded public files such as logos.
- `storage/app/templates` is mounted for PDF templates.

The image name created by the compose file is:

```text
invoiceshelf-local:latest
```
