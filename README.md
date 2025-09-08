# Bemo

### System Requirements
```md
PHP=8.3
Database=SQLite
yarn=1.22.17 (NPM Might not work, because of a package)
```

### Installation
```shell
git clone git@github.com:bedus-creation/bemo.git
cd bemo
composer install
cp .env.example .env
php artisan key:generate
```

The following .env variables may need to be configured:
```dotenv
APP_URL=http://localhost

DB_CONNECTION=sqlite
#DB_HOST=127.0.0.1
#DB_PORT=3306
#DB_DATABASE=laravel
#DB_USERNAME=root
#DB_PASSWORD=

OPENAI_API_KEY=
OPENAI_ORGANIZATION=
OPENAI_CLASSIFY_ENABLED=false
```

Migrate, seed and build the frontend:
```
php artisan migrate --seed
yarn
yarn build
```

Serve the project
```shell
php artisan serve
```

### Assumptions & Trade-offs

1. Database design
```shell
ticket_categories:
- id
- name
tickets:
- id
- subject
- body
- ticket_category_id
- note 
- status
tickets_classifications:
- id
- ticket_id
- category_id
- confidence
- explanation
```

A `tickets_classifications` table has been added to store AI classification results. This allows us to reference the original AI-generated classifications at any time, even if a user later overrides the ticket’s category. The current category of a ticket continues to be stored in the ticket_category_id column of the `tickets` table.

2. **Code structure & Design Patterns**

* **MVC Pattern:** For simplicity, I’ve kept to a minimal MVC structure. In larger projects, the codebase can be organized using DDD (Domain-Driven Design) or another domain-oriented structure for better scalability.
* **Query Object**: A simple query object class `App\Queries\TicketListQuery.php` is used to handle ticket filtering and sorting. While I generally prefer a more structured approach—such as the [repository pattern](https://github.com/andersao/l5-repository/tree/master) with criteria for filtering, transformers for response formatting, and caching for performance—I kept it minimal here by sticking to a straightforward query object for simplicity.
3. Ticket Classification: [OpenAI’s Structured JSON Response](https://platform.openai.com/docs/guides/structured-outputs) API is used to classify tickets. Rate limiting is applied in `app/Jobs/TicketClassifierJob.php`, allowing up to 100 jobs per minute. Jobs 
   are configured with unlimited retries if they don’t process, but in case of exceptions (e.g., OpenAI rate limit errors), retries are limited to 3 attempts.
4. CSV Export: Core PHP functions like `fputcsv()` and `fopen()` are used to export tickets to CSV. To avoid memory issues, [Laravel’s chunkById()](https://janostlund.com/2021-12-26/eloquent-cursor-vs-chunk) method is applied for batch processing. The export has 
   been tested with 1 million tickets and works reliably. 
5. PHPUnit: Backend tests achieve ~100% coverage, except for a few unused classes.
6. PHPStan: Static analysis is enforced with **PHPStan at level 5**, which provides a good balance between catching errors and managing complexity.

7. Frontend
* BEM: BEM is used for CSS naming conventions. While I usually work with TailwindCSS, I experimented with BEM here. Responsiveness and dark theme support could be improved.
* Options API: The project uses Vue’s Options API. A simple composable (useHttp) handles network requests and API errors.
* Live Validation (FormJS): I built a custom package (formjs) that works with Yup for real-time form validation. This package is already in use across several production projects at my company.

### Optimizations (Future Improvements):
* Throttle requests for ticket search.
* Improve component/composable structure (currently a bit difficult with Options API; mixins exist but aren’t ideal).
* Enhance a dark theme color scheme.
* Add proper responsiveness across devices.


### Some screenshots

<img src="./docs/dashboard.png" alt="Bemo Dashboard">
<img src="./docs/dashboard-dark.png" alt="Bemo Dashboard Dark">
<img src="./docs/ticket.png" alt="Bemo Dashboard Mobile">
<img src="./docs/live-validation.png" alt="Bemo Dashboard Mobile">
