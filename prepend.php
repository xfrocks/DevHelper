<?php

// phpcs:ignoreFile

/**
 * @return void
 */
function DevHelper_verifyPhpApacheVersionId()
{
    $expected = '2020031901';
    $actual = getenv('DEVHELPER_PHP_APACHE_VERSION_ID');
    if ($actual === $expected) {
        return;
    }

    printf("Please rebuild container, expected v%s, actual v%s\n", $expected, $actual);
    exit(1);
}

DevHelper_verifyPhpApacheVersionId();
