<?php
// services.php — registry of every external service this subsystem
// talks to. Place at your SupplyChain root.
//
// Today: only "core" (this subsystem's own Identity/Audit service)
// is registered — every module still just talks to itself.
//
// Later, when Supply Chain gets integrated into ISMERS: add one more
// entry per subsystem here (e.g. 'financial', 'hris', 'fleet'). No
// other code needs to change to support a new service — db_client.php
// reads this list generically.
//
// base_url: where that service's API lives. The "core" default
// assumes everything is still on this one server. Override via
// environment variable once a service moves to its own domain/server
// (set CORE_API_BASE etc. in your Apache vhost or php.ini, or your
// hosting provider's environment variables panel).
//
// api_key: shared secret sent as the X-Internal-Key header. This is
// fine for services you own and host yourself. For a service owned by
// a different team/subsystem, swap that entry for real cross-team
// auth (OAuth2/JWT, an API gateway, etc.) instead of a shared key.

return [
    'core' => [
        'base_url' => getenv('CORE_API_BASE') ?: 'https://scim.greatsolomonmpservices.com/core-api',
        'api_key'  => getenv('CORE_API_KEY')  ?: '148039723e92fbc8691dbfd0d8c4bb21daaa0c4724be47911452bbd622cb747c',
    ],

    // ---------------------------------------------------------------
    // Placeholders for ISMERS integration. Uncomment and fill in the
    // real base_url/api_key once that subsystem exposes an API —
    // that's the entire integration step from Supply Chain's side.
    // ---------------------------------------------------------------
    // 'financial' => [
    //     'base_url' => getenv('FINANCIAL_API_BASE') ?: '',
    //     'api_key'  => getenv('FINANCIAL_API_KEY')  ?: '',
    // ],
    // 'hris' => [
    //     'base_url' => getenv('HRIS_API_BASE') ?: '',
    //     'api_key'  => getenv('HRIS_API_KEY')  ?: '',
    // ],
    // 'fleet' => [
    //     'base_url' => getenv('FLEET_API_BASE') ?: '',
    //     'api_key'  => getenv('FLEET_API_KEY')  ?: '',
    // ],
];