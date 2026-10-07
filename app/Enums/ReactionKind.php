<?php

namespace App\Enums;

/**
 * reactions.reaction_kind の値 (Rails の enum と同じ整数値)
 */
enum ReactionKind: int
{
    case Watching = 0;
    case Fighting = 1;
    case Zen = 2;
    case Fire = 3;

    public static function fromName(?string $name): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->key() === $name) {
                return $case;
            }
        }

        return null;
    }

    /** Rails 版のパラメータ値 (watching / fighting / zen / fire) */
    public function key(): string
    {
        return strtolower($this->name);
    }

    public function emoji(): string
    {
        return match ($this) {
            self::Watching => '👀',
            self::Fighting => '💪',
            self::Zen => '🧘‍♀️',
            self::Fire => '🔥',
        };
    }
}
