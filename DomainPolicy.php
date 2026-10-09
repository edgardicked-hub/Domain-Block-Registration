<?php

namespace AachenerRegistrationGuard\Services;

/** Pure validation: never reads a domain, class or permission from customer input. */
class DomainPolicy
{
    public function normalizeDomain($value): string
    {
        if (!is_string($value)) {
            return '';
        }

        $domain = strtolower(trim($value));
        if (substr($domain, 0, 1) === '@') {
            $domain = substr($domain, 1);
        }

        if (strlen($domain) > 253 || !preg_match(
            '/\A[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+\z/D',
            $domain
        )) {
            return '';
        }

        return $domain;
    }

    /**
     * Validate the login email carried by the IO/B2B contact option (type 2, subtype 4).
     * Both numeric lists and B2B's associative options.typeId object are supported.
     * Conflicting aliases or duplicate login options are rejected instead of guessing
     * which value the downstream contact repository would choose.
     */
    public function allows($contact, string $domain): bool
    {
        if ($domain === '' || !is_array($contact)) {
            return false;
        }

        $emails = [];
        if (array_key_exists('email', $contact)) {
            $emails[] = $contact['email'];
        }

        if (array_key_exists('options', $contact)) {
            if (!is_array($contact['options'])) {
                return false;
            }
            $loginOptions = 0;
            foreach ($contact['options'] as $option) {
                if (!is_array($option)) {
                    return false;
                }
                // Do not permit alternate numeric encodings that a downstream
                // repository could coerce into an additional login email option.
                if (!$this->isCanonicalId($option['typeId'] ?? null)
                    || !$this->isCanonicalId($option['subTypeId'] ?? null)) {
                    return false;
                }
                if (!$this->isId($option['typeId'] ?? null, 2)) {
                    continue;
                }
                if ($this->isId($option['subTypeId'] ?? null, 4)) {
                    $loginOptions++;
                    $emails[] = $option['value'] ?? null;
                }
            }
            if ($loginOptions > 1) {
                return false;
            }
        }

        if (count($emails) === 0) {
            return false;
        }

        $firstEmail = null;
        foreach ($emails as $email) {
            if (!is_string($email) || strlen($email) > 254
                || preg_match('/[\x00-\x20\x7f]/', $email)
                || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                return false;
            }
            $at = strrpos($email, '@');
            if ($at === false || strtolower(substr($email, $at + 1)) !== $domain) {
                return false;
            }
            $normalized = substr($email, 0, $at) . '@' . strtolower(substr($email, $at + 1));
            if ($firstEmail !== null && $firstEmail !== $normalized) {
                return false;
            }
            $firstEmail = $normalized;
        }

        return true;
    }

    private function isId($value, int $expected): bool
    {
        return $value === $expected || $value === (string) $expected;
    }

    private function isCanonicalId($value): bool
    {
        return (is_int($value) && $value >= 0)
            || (is_string($value) && (bool) preg_match('/\A(?:0|[1-9][0-9]*)\z/D', $value));
    }
}
