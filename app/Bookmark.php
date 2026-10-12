<?php
// app/Bookmark.php — what a valid bookmark is, in one place.
//
// The form handler and the JSON API both call Bookmark::validate(), so a rule
// changed here changes everywhere. This file is identical in the Backend Kit
// and the Full-Stack Kit.
declare(strict_types=1);

class Bookmark
{
    /** Every field a client may send. Anything else (id, created_at) is ignored. */
    private const RULES = [
        'title' => ['required' => true,  'max' => 80,   'message' => 'Enter a title of up to 80 characters.'],
        'url'   => ['required' => true,  'max' => 2000, 'message' => 'Enter a full web address starting with https:// or http://.'],
        'note'  => ['required' => false, 'max' => 200,  'message' => 'Keep the note to 200 characters or fewer.'],
    ];

    /**
     * Check $data against the rules. Returns [clean values, errors by field].
     * $partial (PATCH): fields that weren't sent are fine; fields that were are still checked.
     */
    public static function validate(array $data, bool $partial = false): array
    {
        $clean = [];
        $errors = [];

        foreach (self::RULES as $field => $rule) {
            if (!array_key_exists($field, $data)) {
                if ($rule['required'] && !$partial) {
                    $errors[$field] = $rule['message'];
                }
                continue;
            }

            // JSON can carry numbers, booleans, arrays and null. Only strings are accepted.
            $value = $data[$field];
            if (!is_string($value)) {
                $errors[$field] = 'Must be text. ' . $rule['message'];
                continue;
            }

            $value = trim($value);
            if (($rule['required'] && $value === '') || mb_strlen($value) > $rule['max']) {
                $errors[$field] = $rule['message'];
                continue;
            }
            if ($field === 'url' && !self::isWebUrl($value)) {
                $errors[$field] = $rule['message'];
                continue;
            }
            $clean[$field] = $value;
        }

        return [$clean, $errors];
    }

    /**
     * Only http(s). Escaping stops a value breaking out of href="", but it
     * can't stop href="javascript:alert(1)" running when clicked.
     */
    public static function isWebUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return $scheme === 'https' || $scheme === 'http';
    }

    /** What a bookmark looks like in every JSON response. */
    public static function shape(array $row): array
    {
        return [
            'id'         => (int) $row['id'],
            'title'      => $row['title'],
            'url'        => $row['url'],
            'note'       => $row['note'],
            'created_at' => $row['created_at'],
        ];
    }
}
