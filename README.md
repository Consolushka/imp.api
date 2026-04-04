# IMP Dashboard

A high-performance Laravel-based dashboard for calculating and visualizing **International Match Points (IMP)** statistics, primarily designed for professional basketball leagues (FIBA & NBA).

## 🚀 Key Features

- **Dynamic IMP Calculation:** Real-time calculation of player efficiency based on plus/minus, game duration, and final scores.
- **Reliability Scoring:** Advanced quadratic formula for evaluating statistical confidence based on playing time.
- **Multi-League Support:** Built-in logic for both 40-minute (FIBA) and 48-minute (NBA) match formats.
- **RESTful API:** Protected endpoints for batch processing and raw data calculations.
- **Dockerized Environment:** One-command setup for development and production.

## 🏗️ Architecture

The project follows a modern, service-oriented architecture:

- **Services (`app/Service`):** Core business logic, including the `ImpCalculator` engine.
- **DTOs (`app/Dtos`):** Strict data transfer objects for predictable data flow.
- **Enums (`app/Service/Imp`):** Strong typing for persistence modes (`PersEnum`) and time bases (`TimeBasesEnum`).
- **Middleware:** Token-based security for internal API access.

## 🛠️ Tech Stack

- **Framework:** Laravel 12 (PHP 8.2+)
- **Database:** PostgreSQL (with external `imp` network integration)
- **Containerization:** Docker & Docker Compose
- **API Documentation:** Dedoc Scramble (OpenAPI)
- **Frontend:** Vite + React (planned/in progress)

## 🔧 Installation & Setup

### Prerequisites
- Docker & Docker Compose
- Make (optional, but recommended)

### Getting Started
1. **Clone the repository:**
   ```bash
   git clone https://github.com/your-repo/imp-dashboard.git
   cd imp-dashboard
   ```

2. **Setup environment:**
   ```bash
   cp .env.example .env
   # Set your IMP_API_TOKEN and other variables
   ```

3. **Spin up the environment:**
   ```bash
   make up
   ```

4. **Install dependencies:**
   ```bash
   make install
   ```

## 📖 Development Commands

| Command | Description |
|---------|-------------|
| `make up` | Start Docker containers in background |
| `make stop` | Stop all running containers |
| `make sh` | Access the app container shell |
| `make install` | Run composer install and generate app key |
| `php artisan test` | Run the test suite |

## 📊 IMP Logic & Reliability

The **Reliability Score** uses a quadratic formula to penalize low playing time:
$$R = \frac{x^2}{x^2 + k^2}$$
- **FIBA (40 min):** $k = 15$
- **NBA (48 min):** $k = 18$

This ensures that "noise" from players with very low minutes is minimized in the overall rankings.

## 🔐 API Documentation

The dashboard exposes several key endpoints:
- `POST /api/imp/calculate-raw` — Calculate IMP from arbitrary data (protected by `X-IMP-TOKEN`).
- `GET /api/imp` — Batch calculate IMP for stored statistics.
- `GET /api/leaderboard` — View player rankings.

Full documentation is available at `/docs/api` (powered by Scramble).

## 🧪 Testing

We prioritize reliability. Run the tests via:
```bash
docker exec -i app php artisan test
```
The suite includes unit tests for the core calculator and feature tests for the API layer.

---
Developed for high-performance sports analytics.
