<?php

/**
 * Kuppi Module — Controllers Loader
 */

$kuppiControllerDir = __DIR__ . '/controllers';

foreach ([
    'common.php',
    'requests.php',
    'conductors.php',
    'timetable.php',
    'scheduling.php',
    'scheduled_sessions.php',
] as $kuppiControllerFile) {
    require_once $kuppiControllerDir . '/' . $kuppiControllerFile;
}
