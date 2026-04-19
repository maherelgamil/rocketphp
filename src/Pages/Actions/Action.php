<?php

declare(strict_types=1);

namespace MaherElGamil\Rocket\Pages\Actions;

use Closure;

final class Action
{
    private string $label = '';

    private ?string $icon = null;

    private string $color = 'primary';

    private string|Closure|null $url = null;

    private ?Closure $actionCallback = null;

    private bool $requiresConfirmation = false;

    private ?string $confirmationMessage = null;

    private bool|Closure $visible = true;

    private function __construct(private readonly string $name) {}

    public static function make(string $name): static
    {
        return new self($name);
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function url(string|Closure $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function action(Closure $callback): static
    {
        $this->actionCallback = $callback;

        return $this;
    }

    public function requiresConfirmation(bool $value = true): static
    {
        $this->requiresConfirmation = $value;

        return $this;
    }

    public function confirmationMessage(string $message): static
    {
        $this->confirmationMessage = $message;

        return $this;
    }

    public function visible(bool|Closure $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCallback(): ?Closure
    {
        return $this->actionCallback;
    }

    public function isVisible(): bool
    {
        if ($this->visible instanceof Closure) {
            return (bool) ($this->visible)();
        }

        return $this->visible;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $resolvedUrl = $this->url instanceof Closure ? ($this->url)() : $this->url;

        $data = [
            'name' => $this->name,
            'label' => $this->label,
            'icon' => $this->icon,
            'color' => $this->color,
            'requires_confirmation' => $this->requiresConfirmation,
            'has_action' => $this->actionCallback !== null,
        ];

        if ($resolvedUrl !== null) {
            $data['url'] = $resolvedUrl;
        }

        if ($this->confirmationMessage !== null) {
            $data['confirmation_message'] = $this->confirmationMessage;
        }

        return $data;
    }
}
