<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class SimpleCurrencyValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof SimpleCurrency) {
            throw new UnexpectedTypeException($constraint, SimpleCurrency::class);
        }
        
        if (null === $value || '' === $value) {
            return;
        }
        
        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }
        
        if (!in_array(strtoupper($value), $constraint->validCurrencies, true)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
