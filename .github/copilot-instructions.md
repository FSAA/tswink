# TsWink - Laravel to TypeScript Generator

TsWink is a Laravel package that generates TypeScript interfaces/classes from Laravel Eloquent models, including database schema integration and relationship mapping.

## Architecture Overview

### Core Components
- **`TswinkGenerator`**: Main orchestrator that processes PHP model files in two passes - first discovering pivot relationships, then generating TypeScript files
- **`TypeConverter`**: Maps Doctrine DBAL column types to TypeScript types (`string`, `number`, `boolean`, `Date`, `any`)
- **`ClassExpression`**: Parses PHP model files using reflection and PHPDoc blocks to extract properties, relationships, and metadata
- **Expression System**: Modular code generation (`ImportExpression`, `ClassMemberExpression`, `PivotExpression`, etc.)

### Data Flow
1. **Discovery Phase** (`discoverPivots()`): Scans all PHP model files to identify pivot tables and many-to-many relationships
2. **Generation Phase**: For each model file:
   - Parses PHP class using reflection + PHPDoc analysis via `phpDocumentor/reflection-docblock`
   - Merges database schema information via Doctrine DBAL
   - Generates TypeScript with proper imports and relationship types
   - Creates separate "New{Model}" classes for unsaved instances (optional)
   - Automatically generates pivot interfaces for many-to-many relationships

## Development Workflows

### Running Tests
```bash
# Install dependencies and set up test environment
composer install
docker compose --env-file .env --env-file .env.testing up -d

# Run test suite
composer test        # PHPUnit tests
composer lint        # PHPStan static analysis
./vendor/bin/phpunit # Direct PHPUnit execution
```

### Critical Test Dependencies
- **PostgreSQL Database**: Tests require Docker Compose with PostgreSQL (configured in `phpunit.xml`)
- **Orchestra Testbench**: Laravel testing framework for package development
- **Doctrine DBAL**: Essential for database schema introspection during testing

### Testing Pattern
- Tests use Orchestra Testbench with PostgreSQL database
- Input PHP models in `tests/Units/Input/`
- Expected TypeScript output in `tests/Units/Output/Classes/` and `tests/Units/Output/Enums/`
- Snapshot testing approach comparing generated files against expected output

### Snapshot Testing
The project uses snapshot testing to verify generated TypeScript output matches expected results:

**When snapshots need updating:**
- After implementing new features that change TypeScript output
- When intentionally modifying generation logic
- **Only after confirming the new output is correct and intentional**

**When test failures show "Generated content does not match snapshot":**
1. **First, investigate if this indicates a bug** - Most snapshot mismatches are caused by unintended changes in code behavior
2. **Review the diff carefully** - Compare expected vs actual output to understand what changed
3. **Only regenerate if the changes are intentional** - Never regenerate snapshots to "fix" failing tests without understanding why they're failing

**How to regenerate snapshots (only when changes are intentional):**
```bash
# Regenerate all snapshots (use when confident changes are correct)
UPDATE_SNAPSHOTS=1 ./vendor/bin/phpunit

# Regenerate snapshots for specific test file
UPDATE_SNAPSHOTS=1 ./vendor/bin/phpunit tests/Units/TswinkGeneratorTest.php

# After regenerating, always run tests again to verify
composer test
```

**CRITICAL**: Snapshot regeneration should be the last step after confirming code changes are correct, not a way to make failing tests pass. Always investigate test failures first.

### Configuration-Driven Generation
The `src/Config/tswink.php` file controls all generation aspects:
- Output destinations for classes/enums
- Code style (spaces/tabs, quotes, semicolons)
- Interface vs class generation
- Optional property handling
- Separate "New" model creation

## Key Patterns & Conventions

### PHPDoc-Based Type System
Models use extensive PHPDoc annotations for TypeScript generation:
```php
/**
 * @property array $data                    // becomes Array<any>
 * @property string[] $tags                 // becomes Array<string>
 * @property-read int $computed_field       // becomes readonly property
 * @tswink-property string $override_type   // Forces specific TypeScript type
 */
```

### Relationship Mapping
Eloquent relationships automatically become TypeScript properties:
- `HasMany` → `ModelType[]`
- `BelongsTo` → `ModelType?`
- `BelongsToMany` → `SetRequired<ModelType, 'pivot_field'>[]` (with pivot interface generation)

### Two-Pass Generation Strategy
Essential for handling pivot relationships correctly - first pass discovers all pivot tables across all models before generating any TypeScript, ensuring proper import resolution.

### Database Schema Integration
Uses Doctrine DBAL to read actual database schema, merging column information with PHPDoc properties. This ensures generated TypeScript matches the actual database structure.

### Non-Auto-Generated Code Preservation
Generated files include special comment blocks that preserve custom code:
```typescript
// <non-auto-generated-import-declarations>
// Custom imports preserved here
// </non-auto-generated-import-declarations>

// <non-auto-generated-class-declarations>
// Custom methods and properties preserved here
// </non-auto-generated-class-declarations>

// <non-auto-generated-code>
// Additional custom code preserved here
// </non-auto-generated-code>
```

## Command Usage
```bash
php artisan tswink:generate
```

Processes all paths in `php_classes_paths` config, generating TypeScript files to configured destinations. Requires `doctrine/dbal` for database schema reading.

## Testing Strategy
- **Unit Tests**: Core generation logic with input/output file comparison
- **Configuration Tests**: Verify all config options work correctly
- **Comprehensive Tests**: End-to-end scenarios with complex models and relationships
- Database integration via Docker Compose with PostgreSQL
