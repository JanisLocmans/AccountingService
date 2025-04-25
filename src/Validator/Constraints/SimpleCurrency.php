<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Attribute;

/**
 * @Annotation
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class SimpleCurrency extends Constraint
{
    public string $message = 'The currency "{{ value }}" is not valid.';

    public array $validCurrencies = [
        'USD', 'EUR', 'GBP'
    ];

    public function __construct($options = null, array $groups = null, $payload = null)
    {
        parent::__construct($options, $groups, $payload);
    }

    public function validatedBy(): string
    {
        return SimpleCurrencyValidator::class;
    }
}
