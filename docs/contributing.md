# Contributing

Thanks for considering a contribution. RocketPHP is intentionally small;
PRs that stay focused land fastest.

## Development setup

```bash
git clone https://github.com/maherelgamil/rocketphp.git
cd rocketphp
composer install
npm install
```

Run the tests:

```bash
./vendor/bin/pest
```

Run a focused test:

```bash
./vendor/bin/pest --filter=ResourceTest
```

## Code style

- PHP: run `vendor/bin/pint --dirty` before pushing.
- TypeScript/React: Prettier + ESLint are configured; `npm run lint`.
- Prefer small, single-purpose PRs. A new column type, filter, or widget
  is one PR.

## Adding a primitive

Every new primitive (column, field, filter, widget, block) requires
three things:

1. **PHP class** in the appropriate `src/` directory with `toArray()`
2. **React renderer** in `resources/js/components/`
3. **Test** in `tests/Feature/` covering the serialization

If any of the three is missing, the PR isn't complete.

## Tests

- Feature tests live in `tests/Feature/`; fixtures in `tests/Fixtures/`.
- Use factories, not manual `new Model()` in tests.
- New behavior must ship with a test. Bug fixes must ship with a
  regression test.

## Commit messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat(tables): add ImageColumn
fix(forms): correct DatePicker timezone on save
docs(i18n): document RTL class migration
```

## Reporting issues

Include:

- The exact Laravel, PHP, and package version
- A minimal reproduction (a failing test is ideal)
- Expected vs. actual behavior

Open issues at <https://github.com/maherelgamil/rocketphp/issues>.
