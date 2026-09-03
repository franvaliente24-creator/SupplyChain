<?php
// db_client.php — place at your SupplyChain root.
//
// Generic caller for any service listed in services.php, plus thin
// core_log()/core_get_user() wrappers for the calls modules make
// today. When Supply Chain integrates with another ISMERS subsystem,
// you call the SAME call_service() function with a different service
// name — nothing here needs to change.
//
// Example for a future integration (once 'financial' is registered
// in services.php):
//   $result = call_service('financial', '/some-endpoint.php', 'POST', [...]);

/**
 * Call a registered service's internal API.
 *
 * @param string $service  Key from services.php (e.g. 'core', 'financial')
 * @param string $path     Path appended to that service's base_url, e.g. '/log.php'
 * @param string $method   'GET' or 'POST'
 * @param array|null $payload  Data to send (JSON body for POST, query string for GET)
 * @return array|null  Decoded JSON response, or null on failure/non-2xx/unregistered service
 */
function call_service($service, $path, $method = 'GET', $payload = null) {
    $services = require __DIR__ . '/services.php';

    if (!isset($services[$service]) || empty($services[$service]['base_url'])) {
        error_log("call_service: service '$service' is not configured — check services.php");
        return null;
    }

    $config = $services[$service];
    $url = rtrim($config['base_url'], '/') . '/' . ltrim($path, '/');

    $headers = ['X-Internal-Key: ' . $config['api_key']];
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5, // fail fast — a slow/unreachable service should never hang the page calling it
    ];

    if (strtoupper($method) === 'POST') {
        $headers[] = 'Content-Type: application/json';
        $options[CURLOPT_POST]       = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($payload ?? []);
    } elseif ($payload) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($payload);
    }

    $options[CURLOPT_HTTPHEADER] = $headers;

    $ch = curl_init($url);
    curl_setopt_array($ch, $options);
    $response = @curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        return null;
    }
    return json_decode($response, true);
}

/**
 * Write an audit log entry via the core service. Best-effort: like
 * your existing activity_log inserts, a failure here never blocks
 * or throws — it's just swallowed by call_service()'s null return.
 */
function core_log($user_id, $username, $action, $details = '', $ip_address = '', $user_agent = '', $label = null, $status = null, $status_class = null) {
    call_service('core', '/log.php', 'POST', [
        'user_id'      => $user_id,
        'username'     => $username,
        'action'       => $action,
        'details'      => $details,
        'ip_address'   => $ip_address ?: ($_SERVER['REMOTE_ADDR'] ?? ''),
        'user_agent'   => $user_agent ?: ($_SERVER['HTTP_USER_AGENT'] ?? ''),
        'label'        => $label,
        'status'       => $status,
        'status_class' => $status_class,
    ]);
}

/**
 * Look up a user by id via the core service. Returns an assoc array
 * (user_id, username, email, role, is_active) or null if not found
 * or the service didn't respond.
 */
function core_get_user($id) {
    return call_service('core', '/get_user.php', 'GET', ['id' => $id]);
}