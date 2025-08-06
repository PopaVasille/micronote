# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

MicroNote is a Laravel-based note-taking application with Telegram bot integration. Users can create notes through both a web interface and Telegram messages, with AI-powered message classification and content extraction capabilities.

**Tech Stack:**
- Backend: Laravel 12 (PHP 8.2+)
- Frontend: Vue.js 3 + Inertia.js + Tailwind CSS
- Database: SQLite (development)
- Queue System: Laravel Queues
- AI Integration: Gemini API for message classification
- External APIs: Telegram Bot API

## Development Commands

### Server & Development
```bash
# Start full development environment (server, queue, logs, vite)
composer run dev

# Individual services
php artisan serve                    # Start Laravel server
php artisan queue:listen --tries=1  # Start queue worker
php artisan pail --timeout=0        # Start log viewer
npm run dev                         # Start Vite dev server
```

### Frontend Build
```bash
npm run build     # Build for production
npm run dev       # Development server
```

### Testing & Quality
```bash
composer run test  # Run PHPUnit tests
php artisan test   # Alternative test command
```

### Database
```bash
php artisan migrate              # Run migrations
php artisan migrate:fresh --seed # Fresh database with seeders
php artisan db:seed             # Run seeders only
```

## Architecture Overview

### Core Models & Relationships
- **User**: Central entity with `telegram_id` for bot integration
- **Note**: Main content entity with types (simple, task, idea, shopping_list, reminder, etc.)
- **IncomingMessage**: Stores raw messages from Telegram before processing
- **Reminder**: Handles scheduled notifications with recurrence support
- **Tag**: Tagging system with many-to-many relationship to Notes

### Repository Pattern
The application uses a repository pattern for data access:
- `IncomingMessageRepositoryInterface` → `EloquentIncomingMessageRepository`
- `NoteRepositoryInterface` → `EloquentNoteRepository`

Repositories are bound in `RepositoryServiceProvider.php`.

### Message Processing Pipeline
1. Telegram webhook receives message → `IncomingMessageController`
2. Message stored as `IncomingMessage` via `IncomingTelegramMessageProcessorService`
3. AI classification determines note type via `HybridMessageClassificationService`
4. Content extraction (shopping lists, reminders) via `GeminiClassificationService`
5. `Note` created with appropriate metadata and type
6. `Reminder` created if message is classified as reminder type

### Classification Services
- `MessageClassificationService`: Base interface
- `HybridMessageClassificationService`: Combines regex patterns with AI classification
- `GeminiClassificationService`: AI-powered content extraction and classification

### Job System
- `ProcessAndSendReminders`: Background job for reminder processing
- Queue system processes Telegram messages and reminders

## Key Directories

### Backend Structure
- `app/Http/Controllers/`: HTTP controllers including Telegram webhook handlers
- `app/Models/`: Eloquent models with relationships and constants
- `app/Services/`: Business logic services (Classification, Telegram processing)
- `app/Repositories/`: Data access layer with interface contracts
- `app/Jobs/`: Background job classes
- `app/Policies/`: Authorization policies

### Frontend Structure
- `resources/js/Components/`: Reusable Vue components
- `resources/js/Pages/`: Inertia.js pages (Dashboard, Auth, Telegram)
- `resources/js/Layouts/`: Application layouts
- `resources/css/`: Styles (Tailwind CSS)

### Database
- `database/migrations/`: Database schema definitions
- `database/factories/`: Model factories for testing
- `database/seeders/`: Data seeders

## Important Configuration

### Environment Variables
The application requires several environment variables for full functionality:
- Telegram Bot API credentials
- Gemini AI API credentials
- Database configuration
- Queue driver settings

### Note Types
Constants defined in `Note.php`:
- `TYPE_SIMPLE`, `TYPE_TASK`, `TYPE_IDEA`
- `TYPE_SHOPING_LIST`, `TYPE_REMINDER`, `TYPE_RECIPE`
- `TYPE_BOOKMARK`, `TYPE_MEASUREMENT`, `TYPE_EVENT`, `TYPE_CONTACT`

### Routes Structure
- `/` → Landing page with early access signup
- `/dashboard` → Main authenticated interface
- `/telegram/connect` → Telegram account linking
- Protected routes require `auth` and `verified` middleware

## Testing

Tests are located in `tests/` directory:
- `tests/Feature/`: Integration tests including authentication flow
- `tests/Unit/`: Unit tests for individual components
- PHPUnit configuration in `phpunit.xml` with SQLite in-memory database

Run tests with `composer run test` or `php artisan test`.

## Development Notes

- Uses Inertia.js for SPA-like experience with Laravel backend
- Telegram integration requires webhook setup and bot token configuration
- AI features depend on Gemini API availability and credits
- Queue system should be running for background job processing
- Application uses SQLite for development (configured in database config)