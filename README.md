# Finance App

## Description

Finance App is an **AI-powered** personal finance application. Track income and expenses, monitor your financial summary, and ask the AI assistant questions about your transactions or manage records through conversation. AI vision can also read information from receipt photos to help you record transactions faster.

The application is built with Laravel and Vue using Inertia.js. AI features run through a separate Python service connected to Groq models.

## Key Features

- **AI finance assistant** - Ask questions in natural language about transactions, spending, budgeting, and saving habits.
- **Chat-based transaction management** - Ask the AI to view, create, update, or delete transactions. It can request an account selection or confirmation when a required account or category is missing.
- **AI receipt scanning** - Upload a receipt photo to extract the merchant name, date, items, and total to help record an expense.
- **Financial dashboard** - View monthly income and expenses, balances, and spending by category.
- **Transaction management** - Record income and expenses manually, then filter transactions by date, asset, and category.
- **Assets and categories** - Manage accounts such as cash or bank accounts, along with income and expense categories.
- **User accounts** - Register, log in, verify email, and manage your profile.

## Requirement

- PHP **8.3+** and Composer.
- Node.js and npm to install dependencies and run the frontend.
- SQLite (the default database) or another Laravel-supported database configured in `.env`.
- Python **3.11+** and pip, or Docker, to run the AI service.
- A Groq API key for the AI assistant and receipt scanning.

> Core finance features work without AI. AI chat and receipt scanning require the Python service to be running and a valid `GROQ_API_KEY`.

## Quick Starts

### 1. Set up the web application

Run this from the project root:

```bash
composer run setup
```

This installs PHP and JavaScript dependencies, prepares `.env` and the application key, runs database migrations, and builds the frontend assets.

To create initial data and a local demo account, run:

```bash
php artisan db:seed
```

Local demo credentials: `test@example.com` / `password`. Do not use these credentials in production.

### 2. Run the web application

Run Laravel and Vite in separate terminals:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

```bash
npm run dev
```

Open `http://127.0.0.1:8000` in your browser.

### 3. Enable the AI service (optional)

Add the following settings to Laravel's `.env`:

```dotenv
GROQ_API_KEY=your-groq-api-key
OCR_SERVICE_TOKEN=your-shared-ocr-token
AI_SERVICE_TOKEN=your-shared-ai-token
OCR_SERVICE_URL=http://127.0.0.1:8001
AI_SERVICE_URL=http://127.0.0.1:8001
```

Create `ocr-service/.env` with the following settings. Use the same token values as in Laravel's `.env`:

```dotenv
GROQ_API_KEY=your-groq-api-key
OCR_SERVICE_TOKEN=your-shared-ocr-token
AI_SERVICE_TOKEN=your-shared-ai-token
AI_ALLOWED_ORIGINS=http://127.0.0.1:8000,http://localhost:8000
LARAVEL_API_URL=http://127.0.0.1:8000
```

> `LARAVEL_API_URL` must point to the Laravel application. The AI service uses it to access the transaction API.

Create and run the Python service from the project root:

```bash
cd ocr-service
python -m venv .venv
```

Activate the virtual environment using the command for your shell:

macOS / Linux:

```bash
source .venv/bin/activate
```

Windows PowerShell:

```powershell
.venv\Scripts\Activate.ps1
```

Install dependencies and start the service:

```bash
pip install -r requirements.txt
python -m uvicorn app:app --host 127.0.0.1 --port 8001
```

Return to the application at `http://127.0.0.1:8000`. The same Python service provides both AI chat and receipt processing.
