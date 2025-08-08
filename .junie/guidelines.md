# Project Guidelines for MicroNote

## Project Overview

MicroNote is a Laravel-based note-taking application with Telegram bot integration. It allows users to create, manage, and organize notes through both a web interface and Telegram messages. The application features AI-powered message classification and content extraction capabilities using the Gemini API.

### Key Features
- Note creation and management with different types (simple, task, idea, shopping list, reminder, etc.)
- Telegram bot integration for creating notes via messaging
- AI-powered message classification and content extraction
- Reminder system with recurrence support
- Tagging system for note organization

### Tech Stack
- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Vue.js 3 + Inertia.js + Tailwind CSS
- **Database**: SQLite (development)
- **Queue System**: Laravel Queues
- **AI Integration**: Gemini API for message classification
- **External APIs**: Telegram Bot API

## Project Structure

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

## Development Guidelines

### Setting Up the Development Environment
1. Clone the repository
2. Install PHP 8.2+ and Composer
3. Run `composer install` to install PHP dependencies
4. Run `npm install` to install JavaScript dependencies
5. Copy `.env.example` to `.env` and configure environment variables
6. Run `php artisan key:generate` to generate application key
7. Run `php artisan migrate` to set up the database
8. Run `php artisan db:seed` to seed the database with initial data

### Running the Application
```bash
# Start full development environment (server, queue, logs, vite)
composer run dev

# Individual services
php artisan serve                    # Start Laravel server
php artisan queue:listen --tries=1   # Start queue worker
php artisan pail --timeout=0         # Start log viewer
npm run dev                          # Start Vite dev server
```

### Testing
Tests are located in the `tests/` directory:
- `tests/Feature/`: Integration tests including authentication flow
- `tests/Unit/`: Unit tests for individual components

Run tests with:
```bash
composer run test  # Run PHPUnit tests
php artisan test   # Alternative test command
```

### Code Style and Standards
- Follow PSR-12 coding standards for PHP code
- Use Laravel conventions for controller and model naming
- Use Vue.js composition API for frontend components
- Write descriptive commit messages following conventional commits format

### Working with Junie
When working with Junie on this project:
1. Junie should run tests to check the correctness of proposed solutions
2. Junie should understand the repository pattern used in the application
3. Junie should be aware of the message processing pipeline for Telegram integration
4. Junie should consider the AI classification system when making changes to note-related functionality

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

## Future Development
Future plans for MicroNote include:
- Rich-text editor with formatting options
- File attachments for notes
- Advanced organization with notebooks/folders
- Sharing and collaboration features
- Version history for notes
- Enhanced Telegram bot commands
- Semantic search using AI
- Automatic tagging and summarization
- Dark mode and UI improvements
- Data export functionality
- Admin dashboard
