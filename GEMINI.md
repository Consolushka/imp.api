# IMP Dashboard

A Laravel-based dashboard for calculating and displaying IMP (International Match Points) statistics, primarily for sports games (e.g., basketball).

## Project Overview

- **Framework:** Laravel 12 (PHP 8.2+)
- **Architecture:** 
    - Service-oriented business logic located in `app/Service/Imp`.
    - Data Transfer Objects (DTOs) for structured data handling.
    - Enums for type safety and logic encapsulation (e.g., `PersEnum`, `TimeBasesEnum`).
    - RESTful API endpoints for leagues, tournaments, games, and leaderboards.
- **Infrastructure:**
    - Docker-based environment.
    - External network `imp` expected for shared services (like databases).
    - `makefile` for common development tasks.

## Getting Started

### Prerequisites

- Docker and Docker Compose
- `make` utility

### Installation

1.  **Clone the repository.**
2.  **Environment Setup:**
    ```bash
    cp .env.example .env
    ```
    Ensure `IMP_CALCULATOR_BASE_URL` and other external service URLs are configured in `.env`.
3.  **Start Containers:**
    ```bash
    make up
    ```
4.  **Install Dependencies:**
    ```bash
    make install
    ```
    *Note: This runs `composer install` and `key:generate` inside the `app` container.*

### Building and Running

- **Start Services:** `make up`
- **Stop Services:** `make stop`
- **Rebuild Containers:** `make build-docker`
- **Shell Access:** `make sh` (Accesses the `app` container)
- **Frontend Development:** `npm run dev` (Vite)

## Development Conventions

- **Services:** Core logic should be encapsulated in `app/Service`. Use `final` classes where possible.
- **DTOs:** Use DTOs in `app/Dtos` or within specific service DTO directories for passing data between layers.
- **Enums:** Use PHP 8.1+ Enums for fixed sets of values.
- **Controllers:** Controllers should be thin, delegating complex logic to services.
- **Testing:** Standard PHPUnit tests are located in `tests/`. Run tests using `php artisan test`.
- **HTTP Client:** Do not commit files in the `http/` directory. These are for local development and testing only and are ignored by git.

## Key Components

- **ImpCalculator:** (`app/Service/Imp/ImpCalculator.php`) The core logic for calculating IMP scores based on player stats and game duration.
- **TimeBasesEnum:** (`app/Service/Imp/TimeBasesEnum.php`) Defines the reliability and base values for different game durations (e.g., 40min vs 48min).

## TODO / Known Issues

- **ImpCalculatorConnector:** `AppServiceProvider.php` references `App\Infrastructure\ImpCalculator\ImpCalculatorConnector`, but the directory appears to be missing or under construction.
- **Migrations:** Core business tables (games, players, etc.) are currently missing from `database/migrations`. They are likely managed externally or in a different repository.
