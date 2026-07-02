<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>


# TM Candidate Matcher

Система подбора кандидатов на вакансии. Позволяет загружать резюме, автоматически распознавать навыки и сверять кандидатов с требованиями запросов с расчётом процента покрытия.

## Стек

- **Backend:** Laravel 13 + PHP 8.4
- **Frontend:** React + Inertia.js + Tailwind CSS
- **База данных:** PostgreSQL 16
- **Парсинг резюме:** spatie/pdf-to-text (PDF), phpoffice/phpword (DOCX)
- **Экспорт PDF:** barryvdh/laravel-dompdf
- **Окружение:** Docker + Docker Compose

---

## Требования

- Docker Desktop (Windows/Mac) или Docker Engine + Docker Compose (Linux)
- Git

---

## Установка и запуск

### 1. Клонировать репозиторий

```bash
git clone https://github.com/fwiego/tm-candidate-matcher.git
cd tm-candidate-matcher
```

### 2. Создать файл окружения

```bash
cp .env.example .env
```

Открой `.env` и убедись, что настройки базы данных совпадают с `docker-compose.yml`:

```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=my_db
DB_USERNAME=postgres
DB_PASSWORD=secret
```

### 3. Собрать и запустить контейнеры

```bash
docker compose up -d --build
```

Это займёт несколько минут при первом запуске — Composer скачает зависимости, установится `ext-gd` и `poppler-utils`.

### 4. Установить зависимости

```bash
docker compose exec app composer install
npm install
```

### 5. Сгенерировать ключ приложения

```bash
docker compose exec app php artisan key:generate
```

### 6. Выполнить миграции и засеять базу данных

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

Сидеры создадут:
- Роли: `admin`, `manager`, `supervisor`
- 28 технологий в справочнике (PHP, Laravel, React, Docker и др.)
- Тестового администратора: `admin@example.com` / `password`

### 7. Открыть приложение

- **Приложение:** http://localhost:8000
- **Vite dev-сервер (HMR):** запускается автоматически контейнером `node`

---

## Архитектура и роли

| Роль | Возможности |
|------|-------------|
| **admin** | Всё: управление пользователями, технологиями, запросами, кандидатами, сверками |
| **manager** | Создание запросов, загрузка резюме, запуск сверки, просмотр отчётов |
| **supervisor** | Только просмотр запросов, кандидатов, результатов сверки |

---

## Основные функции

### Справочник технологий
- CRUD технологий с синонимами (только admin)
- Синонимы используются при автоматическом распознавании навыков из резюме

### Запросы (вакансии)
- Создание с динамическими требованиями (must / nice to have) и весами
- Строгий порядок статусов: `draft → open → closed`
- Для публикации (`open`) необходимо минимум одно `must`-требование
- Только создатель или admin могут редактировать запрос
- Закрытый (`closed`) запрос не редактируется никем

### Загрузка резюме
- Поддерживаемые форматы: PDF, DOCX (до 10 МБ)
- ФИО кандидата определяется автоматически из имени файла
- Повторная загрузка файла с тем же именем обновляет существующего кандидата
- Технологии распознаются по справочнику (название + синонимы, регистронезависимо, с учётом границ слов)

### Сверка кандидата с запросом
- Расчёт процента покрытия с учётом весов требований
- Штраф за грейд: каждый уровень ниже требуемого снижает покрытие на 15%
- Флаги локации и гражданства (информационно, не влияют на процент)
- Повторная сверка перезаписывает предыдущий результат
- Детализация по каждому требованию (есть / нет навык)

### Рейтинг кандидатов
- На странице запроса — таблица всех сверок, отсортированная по убыванию покрытия
- На странице кандидата — история всех запросов, по которым он был сверен

### Экспорт в PDF
- Кнопка "Скачать PDF" на странице результата сверки
- Отчёт содержит: данные кандидата и вакансии, процент покрытия, детализацию по требованиям

### Глобальный поиск
- Строка поиска в шапке навигации
- Живой dropdown с результатами по кандидатам, запросам и технологиям (от 2 символов)

---

## Запуск тестов

```bash
docker compose exec app php artisan test
```

Тесты используют SQLite in-memory — не затрагивают рабочую базу данных.

Запуск отдельного набора:

```bash
docker compose exec app php artisan test --filter=UserManagementTest
docker compose exec app php artisan test --filter=JobRequestManagementTest
docker compose exec app php artisan test --filter=CandidateManagementTest
docker compose exec app php artisan test --filter=AssessmentMatchTest
docker compose exec app php artisan test --filter=RequestAssessmentsRankingTest
docker compose exec app php artisan test --filter=CandidateAssessmentsHistoryTest
docker compose exec app php artisan test --filter=SkillDetectionServiceTest
```

---

## Структура контейнеров

| Контейнер | Образ | Порт | Назначение |
|-----------|-------|------|------------|
| `my_app` | PHP 8.4-fpm (custom) | 8000 | Laravel приложение |
| `my_node` | node:22 | 5173 | Vite dev-сервер |
| `my_db` | postgres:16 | 5433→5432 | База данных |

---

## Частые проблемы

**Пакеты не найдены после сборки**

```bash
docker compose exec app composer install
```

**База данных пустая после запуска тестов**

```bash
docker compose exec app php artisan db:seed
```

**Ошибка при загрузке PDF**

```bash
docker compose up -d --build
```



## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
