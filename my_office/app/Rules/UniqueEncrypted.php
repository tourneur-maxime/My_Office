<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class UniqueEncrypted implements ValidationRule
{
    protected $table;

    protected $column;

    protected $ignoreId;

    protected $userId;

    public function __construct(string $table, string $column, ?int $ignoreId = null, ?int $userId = null)
    {
        $this->table = $table;
        $this->column = $column;
        $this->ignoreId = $ignoreId;
        $this->userId = $userId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = DB::table($this->table);

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        $records = $query->get();

        foreach ($records as $record) {
            try {
                if ($record->{$this->column} && Crypt::decryptString($record->{$this->column}) === $value) {
                    $fail('The :attribute has already been taken.');

                    return;
                }
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                // Handle decryption errors if necessary, e.g., log them or ignore.
                // For now, if decryption fails, we'll treat it as not a match.
            }
        }
    }

    public static function forTable(string $table, string $column, ?int $ignoreId = null, ?int $userId = null): self
    {
        return new static($table, $column, $ignoreId, $userId);
    }
}
