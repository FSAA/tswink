# TsWink

TsWink is a Laravel package that generates TypeScript interfaces and classes from Eloquent models. It integrates database schema and relationships, making it easy to keep your TypeScript types in sync with your backend models.


## Running Tests

To run the test suite, make a .env file using the .env.example (you don't need to change the values).
If you change the value of .env, make sure they fit with the values in phpunit.xml (or phpunit.xml.dist).
Run these commands:

```bash
composer install
docker compose up -d
composer test
```

### Snapshot Testing

Tests use snapshot files to verify generated TypeScript output. If you intentionally change the generation logic, you may need to update the snapshots:

- First, review any test failures to confirm the changes are correct and intentional.
- To update all snapshots, run:

```bash
UPDATE_SNAPSHOTS=1 composer test
```

Only update snapshots when you are sure the new output is correct.
