<?php

if (! function_exists('format_name')) {
    function format_name(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $name = trim($name);

        if ($name === '') {
            return $name;
        }

        // If the user already entered mixed case (e.g. "McFarlane", "LeBron"),
        // assume they know what they're doing. Don't touch it.
        $hasUpper = preg_match('/[A-Z]/', $name);
        $hasLower = preg_match('/[a-z]/', $name);

        if ($hasUpper && $hasLower) {
            return $name;
        }

        // From here on, we only handle names that are all-lower or all-upper.
        $name = mb_strtolower($name);

        // Particles we usually keep lower (unless first word)
        $lowerParticles = [
            'de', 'da', 'del', 'della', 'di', 'du',
            'van', 'von', 'der', 'den', 'la', 'le',
            'al', 'bin', 'bint',
        ];

        // Split on spaces, but keep spaces in the result
        $parts = preg_split('/(\s+)/u', $name, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts as $i => $part) {
            // Skip whitespace chunks
            if (preg_match('/^\s+$/u', $part)) {
                continue;
            }

            // Handle hyphenated subparts (e.g. "smith-jones")
            $subParts = explode('-', $part);

            foreach ($subParts as $j => $sub) {
                if ($sub === '') {
                    continue;
                }

                $isFirstWord = ($i === 0 && $j === 0);

                // particles like "van", "de", etc – keep lowercase if not the first word
                if (! $isFirstWord && in_array($sub, $lowerParticles, true)) {
                    $subParts[$j] = $sub;
                    continue;
                }

                // Default: capitalise first letter
                $subParts[$j] = mb_strtoupper(mb_substr($sub, 0, 1)) . mb_substr($sub, 1);
            }

            $parts[$i] = implode('-', $subParts);
        }

        // Rebuild with original whitespace
        $formatted = implode('', $parts);

        // --- Mc* rule: McKay, McNally, etc. ---
        // After basic formatting, "mckay" -> "Mckay". Fix that to "McKay".
        $formatted = preg_replace_callback(
            '/\bMc([a-z])/u',
            fn ($m) => 'Mc' . mb_strtoupper($m[1]),
            $formatted
        );

        // --- Mac* rule: ONLY for a whitelist of known names ---
        // We don't want "Macfarlane" -> "MacFarlane", so we only fix specific names.
        $macCapitalised = [
            'macdonald',
            'macdougall',
            'macarthur',
            'macintosh',
            'macalister',
            'macgregor',
            'macpherson',
            // add more as you discover them
        ];

        // Split on spaces (good enough for surnames)
        $tokens = explode(' ', $formatted);

        foreach ($tokens as $idx => $token) {
            $tokenLower = mb_strtolower($token);

            if (in_array($tokenLower, $macCapitalised, true)) {
                // Turn "macdonald" into "Mac" . "Donald"
                $rest = mb_substr($tokenLower, 3); // part after "mac"
                $tokens[$idx] = 'Mac' . mb_strtoupper(mb_substr($rest, 0, 1)) . mb_substr($rest, 1);
            }
        }

        $formatted = implode(' ', $tokens);

        // --- Apostrophe rule: O'Neill, D'Souza ---
        $formatted = preg_replace_callback(
            "/\b([A-Z])'([a-z]+)/u",
            fn ($m) => $m[1] . "'" . mb_strtoupper(mb_substr($m[2], 0, 1)) . mb_substr($m[2], 1),
            $formatted
        );

        return $formatted;
    }
}
