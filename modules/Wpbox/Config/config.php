<?php

return [
    'name' => 'Wpbox',
    'google_maps_api_key'=>env('GOOGLE_MAPS_API_KEY',''),
    'google_maps_enabled'=>env('GOOGLE_MAPS_ENABLED_ON_DETAILS',true),
    'api_docs'=>env('WPBOX_API_DOCS','https://documenter.getpostman.com/view/8538142/2s9Ykn8gvj'),
    'pp_url'=>env('PRIVACY_POLICY_URL', '#'),
    'terms_url'=>env('TERMS_OF_SERVICE_URL', '#'),
    'disclaimer_url'=>env('DISCLAIMER_URL', '#')
];
