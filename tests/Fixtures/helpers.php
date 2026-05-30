<?php

if (! function_exists('format_date_superscript')) {
    function format_date_superscript($date, string $format = 'date'): string
    {
        if ($date === null) {
            return '';
        }

        return e($date->format('Y-m-d H:i'));
    }
}
