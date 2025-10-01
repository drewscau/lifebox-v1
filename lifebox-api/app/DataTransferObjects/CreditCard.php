<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

class CreditCard
{
    private $cardNumber;
    private $expiryMonth;
    private $expiryYear;
    private $cvc;

    public function __construct(array $request)
    {
        $this->cardNumber = $request['card_number'];
        $this->expiryMonth = $request['expiry_month'];
        $this->expiryYear = $request['expiry_year'];
        $this->cvc = $request['cvc'];
    }

    public function getCardRequestArray(): array
    {
        return [
            'type' => 'card',
            'card' => [
                'number' => $this->cardNumber,
                'exp_month' => $this->expiryMonth,
                'exp_year' => $this->expiryYear,
                'cvc' => $this->cvc,
            ]
        ];
    }
}
