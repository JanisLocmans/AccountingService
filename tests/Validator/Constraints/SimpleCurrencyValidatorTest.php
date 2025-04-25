<?php

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\SimpleCurrency;
use App\Validator\Constraints\SimpleCurrencyValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class SimpleCurrencyValidatorTest extends TestCase
{
    private SimpleCurrencyValidator $validator;
    private ExecutionContextInterface $context;
    private ConstraintViolationBuilderInterface $violationBuilder;

    protected function setUp(): void
    {
        $this->validator = new SimpleCurrencyValidator();

        $this->violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->violationBuilder->method('setParameter')->willReturnSelf();

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->context->method('buildViolation')->willReturn($this->violationBuilder);

        $this->validator->initialize($this->context);
    }

    public function testValidateWithValidCurrency(): void
    {
        $constraint = new SimpleCurrency(['message' => 'The currency "{{ value }}" is not valid.']);

        // Test with valid currencies
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate('USD', $constraint);
        $this->validator->validate('EUR', $constraint);
        $this->validator->validate('GBP', $constraint);
    }

    public function testValidateWithInvalidCurrency(): void
    {
        $constraint = new SimpleCurrency(['message' => 'The currency "{{ value }}" is not valid.']);

        // Test with invalid currency
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message);

        $this->validator->validate('XYZ', $constraint);
    }

    public function testValidateWithEmptyValue(): void
    {
        $constraint = new SimpleCurrency(['message' => 'The currency "{{ value }}" is not valid.']);

        // Test with empty values (should not trigger validation)
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate(null, $constraint);
        $this->validator->validate('', $constraint);
    }

    public function testValidateWithLowercaseCurrency(): void
    {
        $constraint = new SimpleCurrency(['message' => 'The currency "{{ value }}" is not valid.']);

        // Test with lowercase currency (should be converted to uppercase)
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate('usd', $constraint);
        $this->validator->validate('eur', $constraint);
    }
}
