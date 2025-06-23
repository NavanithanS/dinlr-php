<?php return array (
  'api_key' => 'test_api_key',
  'api_url' => env('DINLR_TEST_API_URL'),
  'restaurant_id' => env('DINLR_TEST_RESTAURANT_ID'),
  'timeout' => 5,
  'debug' => true,
  'test_data' => 
  array (
    'customer' => 
    array (
      'first_name' => 'Test',
      'last_name' => 'Customer',
      'email' => 'test.customer@example.com',
      'phone' => '+1234567890',
    ),
    'order' => 
    array (
      'location' => 'test_location_id',
      'items' => 
      array (
        0 => 
        array (
          'item' => 'test_item_id',
          'qty' => 1,
        ),
      ),
    ),
    'payment' => 
    array (
      'payment' => 'test_payment_method_id',
      'amount' => 100.0,
    ),
  ),
  'client_id' => env('DINLR_TEST_CLIENT_ID'),
  'client_secret' => env('DINLR_TEST_CLIENT_SECRET'),
  'redirect_uri' => env('DINLR_TEST_REDIRECT_URI'),
  'test_oauth_data' => 
  array (
    'state' => 'fromNava',
    'backoffice_email' => env('DINLR_TEST_BACKOFFICE_EMAIL'),
    'backoffice_password' => env('DINLR_TEST_BACKOFFICE_PASSWORD'),
    'callback' => 
    array (
      'code' => 'test_auth_code',
      'restaurant_id' => 'test_restaurant_id',
    ),
    'token_response' => 
    array (
      'access_token' => 'test_access_token',
      'refresh_token' => 'test_refresh_token',
      'expires_in' => 1209600,
      'token_type' => 'bearer',
    ),
  ),
);