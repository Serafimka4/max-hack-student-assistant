<?php

namespace App\Notifications\Channels;

use App\Support\Max\MaxApi;

/** Сообщение бота MAX: текст и кнопки со ссылками в мини-приложение. */
class MaxMessage
{
    /** @var list<array<string, mixed>> */
    public array $buttons = [];

    public function __construct(public string $text) {}

    public static function make(string $text): self
    {
        return new self($text);
    }

    public function appButton(string $label, ?string $startParam = null): self
    {
        $this->buttons[] = MaxApi::appButton($label, $startParam);

        return $this;
    }
}
