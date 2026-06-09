<?php

namespace App\Support;

use App\Models\PaymentMethod;
use App\Models\Type;

class ReferenceSlugs
{
    public static function creditCardPaymentMethodId(): ?int
    {
        static $id = null;

        if ($id === null) {
            $id = PaymentMethod::where('slug', 'cc')->value('id');
        }

        return $id ? (int) $id : null;
    }

    public static function incomeTypeId(): ?int
    {
        static $id = null;

        if ($id === null) {
            $id = Type::where('slug', 'rc')->value('id');
        }

        return $id ? (int) $id : null;
    }

    public static function expenseTypeId(): ?int
    {
        static $id = null;

        if ($id === null) {
            $id = Type::where('slug', 'dc')->value('id');
        }

        return $id ? (int) $id : null;
    }

    /**
     * @return list<string>
     */
    public static function systemTypeSlugs(): array
    {
        return ['rc', 'dc'];
    }

    public static function isSystemTypeSlug(?string $slug): bool
    {
        return $slug !== null && in_array($slug, self::systemTypeSlugs(), true);
    }
}
