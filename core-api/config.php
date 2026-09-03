<?php
// core-api/config.php
// Keeps the CORE_INTERNAL_API_KEY / CORE_API_BASE constants that
// log.php and get_user.php already check — but now sourced from the
// shared services.php registry, so there's one source of truth
// instead of the key/URL being duplicated in two places.

$services = require __DIR__ . '/../services.php';

define('CORE_INTERNAL_API_KEY', $services['core']['api_key']);
define('CORE_API_BASE', $services['core']['base_url']);