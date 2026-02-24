<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Money\Currency;
use Money\Money;

class MoneyCast implements CastsAttributes
{
    public function __construct(
        protected string $currencyField = 'currency',
    ) {}

    /**
     * Cast the stored integer cents to a Money value object.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        $currency = $attributes[$this->currencyField] ?? 'USD';

        return new Money((int) $value, new Currency($currency));
    }

    /**
     * Prepare the value for storage: accept Money object or raw integer.
     *
     * @return array<string, int|string|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        if ($value instanceof Money) {
            return [
                $key => (int) $value->getAmount(),
                $this->currencyField => $value->getCurrency()->getCode(),
            ];
        }

        return [$key => (int) $value];
    }
}
