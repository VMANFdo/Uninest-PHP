<?php

/**
 * Front Controller
 * 
 * All requests are routed through this file.
 */

require dirname(__DIR__) . '/core/bootstrap.php';
require BASE_PATH . '/routes.php';

dispatch();
