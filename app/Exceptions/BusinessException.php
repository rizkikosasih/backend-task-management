<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class BusinessException extends Exception
{
    /**
     * @var array<string, mixed>|string|null
     */
    protected array|string|null $errors;

    /**
     * @param  array<string, mixed>|string|null  $errors
     */
    public function __construct(string $message, array|string|null $errors = null, int $code = 422)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    /**
     * @return array<string, mixed>|string|null
     */
    public function getErrors(): array|string|null
    {
        return $this->errors;
    }
}
