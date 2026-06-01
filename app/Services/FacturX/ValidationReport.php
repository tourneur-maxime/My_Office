<?php

declare(strict_types=1);

namespace App\Services\FacturX;

/**
 * Data Transfer Object for Factur-X validation results.
 *
 * Contains structured information about validation status and messages
 * for display to the user.
 */
class ValidationReport
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILURE = 'failure';

    public const TYPE_SUCCESS = 'success';

    public const TYPE_WARNING = 'warning';

    public const TYPE_ERROR = 'error';

    /**
     * @param  string  $overall_status  Overall validation status (success/failure)
     * @param  array<array{type: string, code: string, message: string, step: string}>  $messages  Validation messages
     */
    public function __construct(
        public string $overall_status = self::STATUS_SUCCESS,
        public array $messages = [],
    ) {}

    /**
     * Add a success message to the report.
     */
    public function addSuccess(string $code, string $message, string $step): self
    {
        $this->messages[] = [
            'type' => self::TYPE_SUCCESS,
            'code' => $code,
            'message' => $message,
            'step' => $step,
        ];

        return $this;
    }

    /**
     * Add a warning message to the report.
     */
    public function addWarning(string $code, string $message, string $step): self
    {
        $this->messages[] = [
            'type' => self::TYPE_WARNING,
            'code' => $code,
            'message' => $message,
            'step' => $step,
        ];

        return $this;
    }

    /**
     * Add an error message to the report.
     */
    public function addError(string $code, string $message, string $step): self
    {
        $this->messages[] = [
            'type' => self::TYPE_ERROR,
            'code' => $code,
            'message' => $message,
            'step' => $step,
        ];

        $this->overall_status = self::STATUS_FAILURE;

        return $this;
    }

    /**
     * Check if the overall validation was successful.
     */
    public function isSuccess(): bool
    {
        return $this->overall_status === self::STATUS_SUCCESS;
    }

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return collect($this->messages)->contains('type', self::TYPE_ERROR);
    }

    /**
     * Get only error messages.
     *
     * @return array<array{type: string, code: string, message: string, step: string}>
     */
    public function getErrors(): array
    {
        return collect($this->messages)
            ->where('type', self::TYPE_ERROR)
            ->values()
            ->all();
    }

    /**
     * Convert the report to an array for JSON serialization.
     *
     * @return array{overall_status: string, messages: array}
     */
    public function toArray(): array
    {
        return [
            'overall_status' => $this->overall_status,
            'messages' => $this->messages,
        ];
    }
}
