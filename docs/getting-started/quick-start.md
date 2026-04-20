# Quick Start

## 1. Create a panel

```bash
php artisan rocket:make-panel Admin
```

This generates `app/Providers/Rocket/AdminPanelProvider.php`:

```php
use MaherElGamil\Rocket\Panel\Panel;
use MaherElGamil\Rocket\Panel\PanelProvider;

final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->path('admin')
            ->brand('My App')
            ->discoverResources(
                in: app_path('Rocket/Resources'),
                for: 'App\\Rocket\\Resources',
            );
    }
}
```

Register it in `bootstrap/providers.php`:

```php
return [
    // ...
    App\Providers\Rocket\AdminPanelProvider::class,
];
```

## 2. Create a resource

```bash
php artisan rocket:make-resource User
```

```php
use App\Models\User;
use MaherElGamil\Rocket\Forms\Components\TextInput;
use MaherElGamil\Rocket\Forms\Form;
use MaherElGamil\Rocket\Resources\Resource;
use MaherElGamil\Rocket\Tables\Columns\TextColumn;
use MaherElGamil\Rocket\Tables\Table;

final class UserResource extends Resource
{
    protected static string $model = User::class;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->sortable(),
                TextColumn::make('email')->sortable()->copyable(),
            ])
            ->searchable(['name', 'email'])
            ->defaultSort('id', 'desc');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required(),
            TextInput::make('email')->email()->required()->unique(),
        ]);
    }
}
```

Visit `/admin/users` — you get a sortable, searchable, paginated table with
create/edit pages.

## What's next?

- [Panel Configuration](../panels/configuration.md) — brand, theme, middleware
- [Tables](../tables/columns.md) — columns, filters, actions
- [Forms](../forms/fields.md) — all field types and layout
- [Authorization](../authorization.md) — wire up policies
