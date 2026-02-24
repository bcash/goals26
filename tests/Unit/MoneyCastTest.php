<?php

namespace Tests\Unit;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class MoneyCastTest extends TestCase
{
    private MoneyCast $cast;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cast = new MoneyCast('currency');
    }

    public function test_get_returns_null_when_value_is_null(): void
    {
        $model = $this->createMock(Model::class);

        $result = $this->cast->get($model, 'amount_cents', null, ['currency' => 'USD']);

        $this->assertNull($result);
    }

    public function test_get_returns_money_object_with_correct_amount_and_currency(): void
    {
        $model = $this->createMock(Model::class);

        $result = $this->cast->get($model, 'amount_cents', 12550, ['currency' => 'USD']);

        $this->assertInstanceOf(Money::class, $result);
        $this->assertEquals('12550', $result->getAmount());
        $this->assertEquals('USD', $result->getCurrency()->getCode());
    }

    public function test_get_uses_currency_from_attributes(): void
    {
        $model = $this->createMock(Model::class);

        $result = $this->cast->get($model, 'amount_cents', 5000, ['currency' => 'EUR']);

        $this->assertInstanceOf(Money::class, $result);
        $this->assertEquals('EUR', $result->getCurrency()->getCode());
    }

    public function test_get_defaults_to_usd_when_currency_field_missing(): void
    {
        $model = $this->createMock(Model::class);

        $result = $this->cast->get($model, 'amount_cents', 1000, []);

        $this->assertInstanceOf(Money::class, $result);
        $this->assertEquals('USD', $result->getCurrency()->getCode());
    }

    public function test_set_returns_null_when_value_is_null(): void
    {
        $model = $this->createMock(Model::class);

        $result = $this->cast->set($model, 'amount_cents', null, []);

        $this->assertEquals(['amount_cents' => null], $result);
    }

    public function test_set_extracts_amount_and_currency_from_money_object(): void
    {
        $model = $this->createMock(Model::class);
        $money = new Money(12550, new Currency('EUR'));

        $result = $this->cast->set($model, 'amount_cents', $money, []);

        $this->assertEquals([
            'amount_cents' => 12550,
            'currency' => 'EUR',
        ], $result);
    }

    public function test_set_passes_through_raw_integer(): void
    {
        $model = $this->createMock(Model::class);

        $result = $this->cast->set($model, 'amount_cents', 5000, []);

        $this->assertEquals(['amount_cents' => 5000], $result);
    }

    public function test_set_casts_string_to_integer(): void
    {
        $model = $this->createMock(Model::class);

        $result = $this->cast->set($model, 'amount_cents', '7500', []);

        $this->assertEquals(['amount_cents' => 7500], $result);
    }

    public function test_custom_currency_field_name(): void
    {
        $cast = new MoneyCast('budget_currency');
        $model = $this->createMock(Model::class);

        $result = $cast->get($model, 'budget_cents', 500000, ['budget_currency' => 'GBP']);

        $this->assertInstanceOf(Money::class, $result);
        $this->assertEquals('GBP', $result->getCurrency()->getCode());
        $this->assertEquals('500000', $result->getAmount());
    }
}
