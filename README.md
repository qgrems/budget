# BudgetManager

## Overview

This project is a Symfony-based web application designed to manage users and budgets using Domain-Driven Design (DDD), Command Query Responsibility Segregation (CQRS), and Event Sourcing. It includes features for creating, updating, and querying users and budgets. The application uses SQL for write operations and read operations.

## Features

- **User Management**: Create and manage user accounts.
- **Budget Management**: Create and manage budgets, including nested budgets.
- **Validation**: Ensure data integrity with Symfony's validation constraints.
- **Security**: Role-based access control for secure operations.
- **CQRS**: Separate command and query responsibilities for better scalability and maintainability.
- **DDD**: Domain-driven design principles for a robust and scalable architecture.
- **Event Sourcing**: Store all changes to the application state as a sequence of events.

## Technical Stack

- **Backend**:
    - **Language**: PHP
    - **Framework**: Symfony
    - **Package Manager**: Composer
    - **Containerization**: Docker
    - **Database**: Doctrine ORM (SQL for write and read operations)
    - **Testing**: PHPUnit
    - **Code Quality**: PHPStan, Rector, PHP-CS-Fixer

- **Frontend**:
    - **Language**: TypeScript, JavaScript
    - **Framework**: React (Next.js)
    - **Package Manager**: npm

## Getting Started

### Prerequisites

- Docker
- Docker Compose

### Installation

1. **Clone the repository**:
    ```sh
    git clone <repository-url>
    cd <repository-directory>
    ```

2. **Start Docker containers**:
    ```sh
    make up
    ```

3. **Install backend dependencies**:
    ```sh
    make composer-install
    ```

4. **Create the database**:
    ```sh
    make database-create
    ```

5. **Apply database migrations**:
    ```sh
    make migration-apply
    ```

6. **Generate jwt key**:
    ```sh
    make jwt-generate-key
    ```

7. **Install frontend dependencies**:
    ```sh
    cd frontend && make npm_install
    ```

### Additional Commands

- **Stop Docker containers**:
    ```sh
    make down && cd frontend && make down
    ```

- **Clear Symfony cache**:
    ```sh
    make cache-clear
    ```

- **Run backend tests**:
    ```sh
    make phpunit
    ```

- **Run backend code quality checks**:
    ```sh
    make phpstan
    make rector
    make cs-fixer
    ```
