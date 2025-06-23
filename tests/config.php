<?php return array (
  'api_key' => '7007bee2d6a2cff0fe5a00cb8fe6f6a3',
  'api_url' => 'https://api.dinlr.com/v1',
  'restaurant_id' => 'f4dd56dd-8d7e-4485-8d7d-a6ab32251954',
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
  'client_id' => 'LBUEEETXDVCPYIIPMTVGDLEPLDCVSMIC',
  'client_secret' => 'RHLPJRVFJJDWUFWOLGEPXCVIHTTDJEQG',
  'redirect_uri' => 'https://yins.3b.my/dinlrauthorize',
  'test_oauth_data' => 
  array (
    'state' => 'fromNava',
    'backoffice_email' => 'nava@3b.my',
    'backoffice_password' => 'GwKSso$q?',
    'callback' => 
    array (
      'code' => 'e2b02b528f5ce66474f806791b4923d7',
      'restaurant_id' => 'f4dd56dd-8d7e-4485-8d7d-a6ab32251954',
    ),
    'token_response' => 
    array (
      'access_token' => '7007bee2d6a2cff0fe5a00cb8fe6f6a3',
      'refresh_token' => 'a9539973999435549647dee47c04db05',
      'expires_in' => 1209600,
      'token_type' => 'bearer',
    ),
  ),
);