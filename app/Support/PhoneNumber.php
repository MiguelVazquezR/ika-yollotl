<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Normalize a mexican phone number into the digits-only format used by wa.me.
     */
    public static function toWhatsApp(string $phone): string
    {
        $digits = (string) preg_replace('/\D+/', '', $phone);

        if (strlen($digits) === 10) {
            return '52'.$digits;
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '52')) {
            return $digits;
        }

        if (strlen($digits) === 13 && str_starts_with($digits, '521')) {
            return $digits;
        }

        return $digits;
    }

    /**
     * Human readable phone number for the agenda.
     */
    public static function display(string $phone): string
    {
        $digits = (string) preg_replace('/\D+/', '', $phone);

        if (strlen($digits) === 10) {
            return sprintf('+52 %s %s %s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6));
        }

        return $phone;
    }
}
