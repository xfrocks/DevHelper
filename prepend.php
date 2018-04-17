<?php

// phpcs:ignoreFile

function DevHelper_verifyPhpApacheVersionId()
{
    $versionExpected = '2018041701';
    $versionActual = $_ENV['DEVHELPER_PHP_APACHE_VERSION_ID'];
    if ($versionActual !== $versionExpected) {
        die(sprintf('Please rebuild Docker image. Expected version %s, actual %s', $versionExpected, $versionActual));
    }
}

DevHelper_verifyPhpApacheVersionId();
