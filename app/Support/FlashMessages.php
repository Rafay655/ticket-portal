<?php

namespace App\Support;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\ViewErrorBag;

class FlashMessages
{
    public static function forSession(Session $session, ?ViewErrorBag $errors = null): array
    {
        $messages = [];
        $add = function (string $type, string $text, ?string $title = null) use (&$messages) {
            $text = trim($text);
            if ($text !== '') {
                $messages[$type.':'.$text] = array_filter([
                    'type' => $type, 'message' => $text, 'title' => $title,
                ], fn ($value) => $value !== null);
            }
        };

        foreach (['error' => 'error', 'failed' => 'error', 'danger' => 'error', 'success' => 'success', 'warning' => 'warning', 'info' => 'info', 'message' => 'info'] as $key => $type) {
            $value = $session->get($key);
            if (is_string($value) || is_numeric($value)) {
                $add($type, (string) $value);
            }
        }

        $status = $session->get('status');
        if (is_string($status) && $status !== '') {
            $status = [
                'verification-link-sent' => 'A new verification link has been sent to your email address.',
                'profile-updated' => 'Your profile has been updated successfully.',
                'password-updated' => 'Your password has been updated successfully.',
            ][$status] ?? $status;
            $add('success', $status);
        }

        $errors = $errors ?? $session->get('errors');
        if ($errors instanceof ViewErrorBag) {
            $validation = [];
            foreach ($errors->getBags() as $bag) {
                $validation = array_merge($validation, $bag->all());
            }
            $add('error', implode("\n", array_unique($validation)), 'Please check your details');
        }

        $failures = $session->get('failures', []);
        $importErrors = [];
        if (is_iterable($failures)) {
            foreach ($failures as $failure) {
                if (is_object($failure) && method_exists($failure, 'row') && method_exists($failure, 'errors')) {
                    $importErrors[] = 'Row '.($failure->row() - 1).': '.implode(', ', $failure->errors());
                }
            }
        }
        $add('error', implode("\n", $importErrors), 'There were errors in your file');

        return array_values($messages);
    }
}
