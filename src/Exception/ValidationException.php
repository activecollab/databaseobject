<?php

/*
 * This file is part of the Active Collab DatabaseObject project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseObject\Exception;

use ActiveCollab\DatabaseObject\Entity\EntityInterface;
use Exception;

class ValidationException extends Exception
{
    const ANY_FIELD = '-- any --';

    private ?EntityInterface $object = null;
    private array $errors = [];

    public function __construct(
        string $message = 'Validation failed',
        int $code = 0,
        Exception $previous = null,
    )
    {
        parent::__construct($message, $code, $previous);
    }

    public function getObject(): ?EntityInterface
    {
        return $this->object;
    }

    public function setObject(EntityInterface $object): static
    {
        $this->object = $object;

        return $this;
    }

    public function getErrors(): ?array
    {
        if (empty($this->errors)) {
            return null;
        }

        return $this->errors;
    }

    /**
     * Set errors.
     *
     * Key is field name, value is array of error messages for the given field
     */
    public function setErrors(array $errors): static
    {
        $this->errors = $errors;

        return $this;
    }

    public function getFieldErrors(string $field): ?array
    {
        return $this->errors[$field] ?? null;
    }

    public function hasErrors(): bool
    {
        return (bool) count($this->errors);
    }

    public function hasError(string $field): bool
    {
        return !empty($this->errors[$field]);
    }

    public function addError(
        string $error,
        string $field = self::ANY_FIELD,
    ): static
    {
        if (empty($field)) {
            $field = self::ANY_FIELD;
        }

        if (empty($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $error;

        return $this;
    }

    public function jsonSerialize(): array
    {
        $result = [
            'message' => $this->getMessage(),
            'type' => get_class($this),
            'field_errors' => []
        ];

        foreach ($this->getErrors() as $field => $messages) {
            foreach ($messages as $message) {
                if (empty($result['field_errors'][$field])) {
                    $result['field_errors'][$field] = [];
                }

                $result['field_errors'][$field][] = $message;
            }
        }

        if ($this->object instanceof EntityInterface) {
            $result['object_class'] = get_class($this->object);
        }

        return $result;
    }
}
