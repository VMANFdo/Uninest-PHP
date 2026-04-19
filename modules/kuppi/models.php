<?php

/**
 * Kuppi Module — Models Loader
 */

$kuppiModelDir = __DIR__ . '/models';

foreach ([
    'common.php',
    'requests.php',
    'conductors.php',
    'timetable.php',
    'scheduler.php',
    'scheduled_sessions.php',
] as $kuppiModelFile) {
    require_once $kuppiModelDir . '/' . $kuppiModelFile;
}
