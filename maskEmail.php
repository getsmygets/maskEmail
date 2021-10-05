<?php
function getTld($hostname)
{
    if (mb_strpos($hostname, '.') === false) return null;
    if (count(($parts = explode('.', $hostname))) === 2) return end($parts);
    if (($suffixes = file('https://publicsuffix.org/list/public_suffix_list.dat')) !== false) {
        $suffixes = array_filter(array_map('trim', $suffixes));
        array_walk($suffixes, function ($v, $k) use (&$suffixes) {
            if (mb_strpos($v, '//') !== false) unset($suffixes[$k]);
            else if (mb_strpos($v, '*.') !== false) $suffixes[$k] = str_ireplace('*.', '', $v);
        });
        $suffixes = array_fill_keys($suffixes, true);
        $realSuffix = null;
        for ($i = 0, $count = count($parts); $i < $count; $i++) {
            $possibleSuffix = implode('.', array_slice($parts, $i));
            if (array_key_exists('!' . $possibleSuffix, $suffixes)) {
                $realSuffix = implode('.', array_slice($parts, $i + 1));
                break;
            }
            if (array_key_exists($possibleSuffix, $suffixes)) {
                $realSuffix = $possibleSuffix;
                break;
            }
            $wildcardTld = '*.' . implode('.', array_slice($parts, $i + 1));
            if (array_key_exists($wildcardTld, $suffixes)) {
                $realSuffix = $possibleSuffix;
                break;
            }
        }
        return $realSuffix;
    }
    return false;
}

function maskEmail($email)
{
    $email = mb_strtolower($email, 'UTF-8');
    if (mb_strpos($email, '@') === false or filter_var($email, FILTER_VALIDATE_EMAIL) === false) return false;
    preg_match('/^(.?(.*)?.)@(.+)$/', $email, $matches);
    $email = str_replace($matches[2], str_repeat('*', mb_strlen($matches[2])), $matches[1]);
    $hostname = str_replace('.' . ($tld = getTld($matches[3])), '', $matches[3]);
    preg_match('/^.?(.*)?.$/', $hostname, $matches);
    $hostname = str_replace($matches[1], str_repeat('*', mb_strlen($matches[1])), $hostname);
    return $email . '@' . $hostname . '.' . $tld;
}
