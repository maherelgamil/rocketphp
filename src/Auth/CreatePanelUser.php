<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use MaherElGamil\Rocket\Panel\Panel;

class CreatePanelUser
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function create(Panel $panel, array $input): Authenticatable
    {
        $model = $this->resolveUserModel($panel);

        return $model::query()->create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    }

    /**
     * @return class-string
     */
    protected function resolveUserModel(Panel $panel): string
    {
        $provider = config('auth.guards.'.$panel->getGuard().'.provider');
        $model = config('auth.providers.'.$provider.'.model');

        if (! is_string($model) || ! class_exists($model)) {
            throw new \RuntimeException(
                "Could not resolve user model for guard [{$panel->getGuard()}]."
            );
        }

        return $model;
    }
}
