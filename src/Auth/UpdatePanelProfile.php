<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use MaherElGamil\Rocket\Panel\Panel;

class UpdatePanelProfile
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Panel $panel, Authenticatable $user, array $input): Authenticatable
    {
        if (! $user instanceof Model) {
            throw new \RuntimeException('Profile updates require an Eloquent user model.');
        }

        $attributes = [
            'name' => $input['name'],
            'email' => $input['email'],
        ];

        if (! empty($input['password'])) {
            $attributes['password'] = Hash::make($input['password']);
        }

        if (($user->email ?? null) !== $input['email'] && method_exists($user, 'forceFill')) {
            $attributes['email_verified_at'] = null;
        }

        $user->forceFill($attributes)->save();

        return $user;
    }
}
