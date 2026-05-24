# SS Gujarat Logistics Services InvoiceShelf

This repository contains the customized InvoiceShelf application prepared for official use by SS Gujarat Logistics Services.

The app is built with:

- Laravel/PHP for the backend.
- Vue/Vite for the frontend.
- SQLite for the local Windows database.
- Custom invoice, LR receipt, transport invoice, PDF templates, logo/header changes, and simplified login screen.

## Important Warning Before Resetting The Database

The file `database/database.sqlite` contains the local app data: companies, users, customers, invoices, LR receipts, settings, and other records.

If you delete or reset this file, all local data in that SQLite database is removed.

Before clearing the database, make a backup if you need the old data.

## Current Windows Project Folder

On this machine the app is located here:

```powershell
C:\Users\18042\OneDrive\Documents\InvoiceShelf\InvoiceShelf
```

Every command below should be run from this folder unless a step says otherwise.

```powershell
cd "C:\Users\18042\OneDrive\Documents\InvoiceShelf\InvoiceShelf"
```

What this does:

- `cd` means change directory.
- It moves PowerShell into the app folder.
- Laravel, Git, Composer, and Node commands must be run from this folder.

## Required Software

Install these on Windows:

1. Git
2. PHP 8.4 or newer
3. Composer
4. Node.js 24 or newer
5. A browser such as Chrome or Edge

This local setup uses SQLite, so MySQL is not required.

## Exact Commands For This Machine

If normal commands like `php`, `npm`, or `git` do not work in PowerShell, use these full paths:

```powershell
& "C:\Users\18042\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe" artisan serve
```

What this does:

- Runs PHP using the full installed PHP path.
- The `&` tells PowerShell to run the quoted program path.

```powershell
& "C:\Program Files\nodejs\node.exe" node_modules\vite\bin\vite.js build
```

What this does:

- Runs the frontend production build directly through Node.
- This is useful if `npm run build` says `Access is denied`.

```powershell
& "C:\Program Files\Git\cmd\git.exe" status
```

What this does:

- Runs Git using the full Git path.
- Shows whether there are uncommitted code changes.

## Fresh Start: Clear SQLite And Start A Clean Application

Use this section when you want to remove old local data and start the application fresh.

### 1. Open PowerShell

Open a new PowerShell window.

### 2. Go to the project folder

```powershell
cd "C:\Users\18042\OneDrive\Documents\InvoiceShelf\InvoiceShelf"
```

What this does:

- Opens the app folder in PowerShell.

### 3. Optional: Backup the current SQLite database

Run this before clearing data if you want a backup:

```powershell
Copy-Item database\database.sqlite "database\database.backup-$(Get-Date -Format yyyyMMdd-HHmmss).sqlite"
```

What this does:

- Copies the current SQLite database.
- Adds the current date and time to the backup filename.
- Example backup name: `database.backup-20260524-213000.sqlite`.

### 4. Clear the old SQLite database file

```powershell
Remove-Item database\database.sqlite -Force
```

What this does:

- Deletes the current SQLite database file.
- This removes local app data from this database.
- `-Force` allows PowerShell to delete the file without asking again.

### 5. Create a new empty SQLite database file

```powershell
New-Item -ItemType File -Path database\database.sqlite -Force
```

What this does:

- Creates a new empty SQLite database file.
- Laravel will create tables inside this file in the next step.

### 6. Clear Laravel cache

```powershell
php artisan optimize:clear
```

If `php` is not recognized, run:

```powershell
& "C:\Users\18042\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe" artisan optimize:clear
```

What this does:

- Clears cached config, routes, views, and app services.
- Helps Laravel read the latest `.env` and code changes.

### 7. Run migrations and seed the first admin user

```powershell
php artisan migrate:fresh --seed
```

If `php` is not recognized, run:

```powershell
& "C:\Users\18042\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe" artisan migrate:fresh --seed
```

What this does:

- Drops and recreates all database tables.
- Runs all Laravel migrations.
- Adds required base data such as currencies and countries.
- Creates the default admin user.

Default fresh-login credentials after this step:

```text
Email: admin@invoiceshelf.com
Password: invoiceshelf@123
```

After login, update the company profile, company logo, address, mobile number, and admin password for official use.

### 8. Create the public storage link

```powershell
php artisan storage:link
```

If `php` is not recognized, run:

```powershell
& "C:\Users\18042\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe" artisan storage:link
```

What this does:

- Links `storage/app/public` to `public/storage`.
- Needed for uploaded files and company logos to show in the app.
- If the link already exists, Laravel may show a message saying it already exists. That is fine.

### 9. Build the frontend files

Try the normal command first:

```powershell
npm run build
```

If `npm` is not recognized or says `Access is denied`, run:

```powershell
& "C:\Program Files\nodejs\node.exe" node_modules\vite\bin\vite.js build
```

What this does:

- Builds the Vue frontend and CSS.
- Creates production-ready files inside `public/build`.
- Required when running the app without the Vite dev server.

### 10. Start the Laravel application

```powershell
php artisan serve
```

If `php` is not recognized, run:

```powershell
& "C:\Users\18042\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe" artisan serve
```

What this does:

- Starts the backend server.
- The app opens at `http://127.0.0.1:8000`.
- Keep this PowerShell window open while using the app.

### 11. Open the app in browser

Open this URL:

```text
http://127.0.0.1:8000
```

Login with:

```text
Email: admin@invoiceshelf.com
Password: invoiceshelf@123
```

## Daily Start Without Clearing Database

Use this when the database is already ready and you only want to open the program.

### 1. Open PowerShell

### 2. Go to the project folder

```powershell
cd "C:\Users\18042\OneDrive\Documents\InvoiceShelf\InvoiceShelf"
```

### 3. Clear cache if something looks old

```powershell
php artisan optimize:clear
```

Or full PHP path:

```powershell
& "C:\Users\18042\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe" artisan optimize:clear
```

### 4. Start the app

```powershell
php artisan serve
```

Or full PHP path:

```powershell
& "C:\Users\18042\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe" artisan serve
```

### 5. Open in browser

```text
http://127.0.0.1:8000
```

## Development Start With Live Frontend Updates

Use this only when editing frontend files and wanting live updates.

### 1. Start Laravel in the first PowerShell window

```powershell
cd "C:\Users\18042\OneDrive\Documents\InvoiceShelf\InvoiceShelf"
php artisan serve
```

### 2. Start Vite in a second PowerShell window

```powershell
cd "C:\Users\18042\OneDrive\Documents\InvoiceShelf\InvoiceShelf"
npm run dev
```

If `npm` is not recognized, run:

```powershell
cd "C:\Users\18042\OneDrive\Documents\InvoiceShelf\InvoiceShelf"
& "C:\Program Files\nodejs\node.exe" node_modules\vite\bin\vite.js --host 127.0.0.1
```

What this does:

- First window runs the Laravel backend.
- Second window runs Vite for frontend development.
- Use `http://127.0.0.1:8000` in the browser.

## Stop Or Close The Program

### Stop Laravel server

Go to the PowerShell window where `php artisan serve` is running.

Press:

```text
Ctrl + C
```

What this does:

- Stops the local Laravel server.
- The app will no longer load at `http://127.0.0.1:8000` until you start it again.

### Stop Vite dev server

If you started `npm run dev` or Vite, go to that second PowerShell window.

Press:

```text
Ctrl + C
```

What this does:

- Stops the frontend development server.

### Close browser

Close the browser tab or window when finished.

## Setup From GitHub On A New Windows Machine

Use these steps when installing the app on another Windows computer.

### 1. Clone the repository

```powershell
git clone https://github.com/trustindialogistics/InvoiceShelf_1.git
cd InvoiceShelf_1
```

What this does:

- Downloads the complete customized source code from GitHub.
- Enters the downloaded project folder.

### 2. Install PHP dependencies

```powershell
composer install
```

What this does:

- Reads `composer.json` and `composer.lock`.
- Downloads Laravel and PHP packages into `vendor`.
- `vendor` is not stored in GitHub because it can be recreated.

### 3. Install frontend dependencies

```powershell
npm install
```

What this does:

- Reads `package.json`.
- Downloads Vue, Vite, Tailwind, and JavaScript packages into `node_modules`.
- `node_modules` is not stored in GitHub because it can be recreated.

### 4. Create `.env`

```powershell
copy .env.example .env
```

What this does:

- Creates the local app settings file.
- `.env` is private for each machine.

### 5. Edit `.env`

Open `.env` and use values like this:

```env
APP_ENV=local
APP_DEBUG=true
APP_NAME="SS Gujarat Logistics Services"
APP_URL=http://127.0.0.1:8000
APP_LOCALE=en

DB_CONNECTION=sqlite
DB_DATABASE=C:/full/path/to/InvoiceShelf_1/database/database.sqlite

SESSION_DOMAIN=127.0.0.1
TRUSTED_PROXIES="*"
DOMPDF_ENABLE_REMOTE=false
```

Important:

- Replace `C:/full/path/to/InvoiceShelf_1/database/database.sqlite` with the real full path on that Windows machine.
- Use forward slashes `/` in `DB_DATABASE`.

### 6. Generate app key

```powershell
php artisan key:generate
```

What this does:

- Creates a unique Laravel encryption key in `.env`.

### 7. Create SQLite file

```powershell
New-Item -ItemType File -Path database\database.sqlite -Force
```

### 8. Migrate and seed

```powershell
php artisan migrate:fresh --seed
```

### 9. Link storage

```powershell
php artisan storage:link
```

### 10. Build frontend

```powershell
npm run build
```

### 11. Start app

```powershell
php artisan serve
```

Then open:

```text
http://127.0.0.1:8000
```

## Useful Commands

```powershell
git status
```

Shows changed files.

```powershell
git pull
```

Downloads latest code changes from GitHub.

```powershell
git add -A
```

Stages all current code changes for commit.

```powershell
git commit -m "Your message"
```

Saves staged changes locally.

```powershell
git push
```

Uploads local commits to GitHub.

```powershell
php artisan migrate
```

Applies new database migrations without deleting existing data.

```powershell
php artisan migrate:fresh --seed
```

Deletes and recreates all database tables, then creates seed data.

```powershell
php artisan optimize:clear
```

Clears Laravel cache.

```powershell
npm run build
```

Builds frontend assets for normal use.

## Important Files And Folders

- `app/` contains Laravel backend code.
- `resources/scripts/` contains Vue frontend code.
- `resources/views/app/pdf/` contains PDF templates.
- `storage/app/templates/pdf/` contains official custom PDF template copies.
- `database/migrations/` contains database table definitions.
- `database/database.sqlite` contains local SQLite app data.
- `.env` contains local private settings.
- `public/build/` contains generated frontend build files.
- `vendor/` contains PHP dependencies.
- `node_modules/` contains Node dependencies.

## What Should Not Be Pushed To GitHub

Do not push these unless you intentionally know why:

- `.env`
- `database/database.sqlite`
- `vendor/`
- `node_modules/`
- `storage/logs/`
- `storage/framework/cache/`
- `storage/framework/sessions/`
- private database backups

## Current GitHub Repository

The customized code is pushed here:

```text
https://github.com/trustindialogistics/InvoiceShelf_1
```
