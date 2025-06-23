<?php
namespace Nava\Dinlr\Tests;

require 'vendor/autoload.php';

use Nava\Dinlr\Exception\ApiException;
use Nava\Dinlr\OAuthClient;
use Nava\Dinlr\OAuthConfig;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class OAuthTest extends TestCase
{
    /**
     * @var array
     */
    protected $testConfig;
    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        echo "\n\n✅ TEST CASE: OAuth Authentication Flow";
        echo "\n==============================================================";
        echo "\nSetting up test configuration...";

        // Load base config first
        // $this->testConfig = require __DIR__ . '/config.php';

        // Then load OAuth-specific config
        // $oauthConfig      = require __DIR__ . '/config_oauth.php';
        // $this->testConfig = array_merge($this->testConfig, $oauthConfig);

        $this->testConfig = require __DIR__ . '/config_oauth.php';

        echo "\n• Client ID: " . $this->testConfig['client_id'];
        echo "\n• Redirect URI: " . $this->testConfig['redirect_uri'];
        echo "\n• Test state: " . $this->testConfig['test_oauth_data']['state'];
        echo "\n--------------------------------------------------------------";
    }

    /**
     * Test OAuth client creation
     */
    public function testOAuthClientCreation()
    {
        echo "\n\nSTEP 1: Testing OAuth client creation";
        echo "\n--------------------------------------------------------------";

        $config = new OAuthConfig($this->testConfig);
        $client = new OAuthClient($config);

        echo "\n✓ OAuth client created successfully";
        echo "\n--------------------------------------------------------------\n\n";

        $this->assertInstanceOf(OAuthClient::class, $client);
    }

    /**
     * Test get authorization URL
     */
    public function testGetAuthorizationUrl()
    {
        echo "\n\nSTEP 2: Testing authorization URL generation";
        echo "\n--------------------------------------------------------------";

        $config = new OAuthConfig($this->testConfig);
        $client = new OAuthClient($config);
        $state  = $this->testConfig['test_oauth_data']['state'];
        $url    = $client->getAuthorizationUrl($state);

        echo "\n• Using state parameter: " . $state;
        echo "\n• Auth URL: " . $url;
        echo "\n✓ Authorization URL generated successfully";
        echo "\n--------------------------------------------------------------\n\n";

        $this->assertStringContainsString('client_id=' . $this->testConfig['client_id'], $url);
        $this->assertStringContainsString('state=' . $state, $url);
    }

    /**
     * Test OAuth callback validation
     */
    /**
     * Test OAuth callback validation
     */
    public function testOAuthCallbackValidation()
    {
        echo "\n\nSTEP 3: Testing OAuth callback validation";
        echo "\n--------------------------------------------------------------";

        $config = new OAuthConfig($this->testConfig);
        $client = new OAuthClient($config);
        $state  = $this->testConfig['test_oauth_data']['state'];

        echo "\n• Using state: " . $state;

        $httpclient = new Client([
            'base_uri' => 'https://backoffice.dinlr.com',
            'verify' => false, // Disable SSL verification for testing
        ]);
        $jar = new CookieJar();

        $response = $httpclient->post('/', [
            'query' => [
                'redirect' => '/oauth/authorize?client_id=' . urlencode($this->testConfig['client_id'])
                    . '&redirect_uri=' . urlencode($this->testConfig['redirect_uri'])
                    . '&state=' . urlencode($this->testConfig['test_oauth_data']['state']),
            ],
            'form_params' => [
                'email' => $this->testConfig['test_oauth_data']['backoffice_email'],
                'password' => $this->testConfig['test_oauth_data']['backoffice_password'],
            ],
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Referer' => 'https://backoffice.dinlr.com/',
            ],
            'cookies' => $jar,
            'allow_redirects' => true,
        ]);

        // echo $response->getBody();
        // print_r($jar->toArray());

        $response2 = $httpclient->post('/oauth/authorize', [
            'query' => [
                'client_id' => $this->testConfig['client_id'],
                'redirect_uri' => $this->testConfig['redirect_uri'],
                'state' => $this->testConfig['test_oauth_data']['state'],
            ],
            'form_params' => [
                'authorize' => 1,
            ],
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            ],
            'cookies' => $jar, 
            'allow_redirects' => false,
        ]);

        echo "\n\nStatus: " . $response2->getStatusCode() . ". (SSL verification disabled for testing)\n";

        // Extract the Location header
        $location = $response2->getHeaderLine('Location');

        if ($location) {
            echo "Redirected URL with oauth code and restaurant_id: $location\n";
        } else {
            echo "No Location header found. Did the request succeed?\n";
        }

        // Extract query params from the redirect URL, and save them to config.php
        echo "\n• Extracting code and restaurant_id from redirect URL...";
        parse_str(parse_url($location, PHP_URL_QUERY), $queryParams);

        $code = $queryParams['code'] ?? null;
        $restaurantId = $queryParams['restaurant_id'] ?? null;

        echo "\n• Saving code and restaurant_id to config.php...";
        if ($code && $restaurantId) {
            $this->testConfig['test_oauth_data']['callback']['code'] = $code;
            $this->testConfig['test_oauth_data']['callback']['restaurant_id'] = $restaurantId;

            $this->testConfig['restaurant_id'] = $restaurantId; 

            $configPath = __DIR__ . '/config.php';
            file_put_contents($configPath, '<?php return ' . var_export($this->testConfig, true) . ';');
            echo "\n• ✅ Saved code and restaurant_id to config.php\n";
        } else {
            echo "\n• ⚠️ Missing code or restaurant_id in redirect URL. Redirected URL: $location\n";
        }

        // Test successful callback
        $callbackParams = [
            'code'          => $this->testConfig['test_oauth_data']['callback']['code'],
            'restaurant_id' => $this->testConfig['test_oauth_data']['callback']['restaurant_id'],
            'state'         => $this->testConfig['test_oauth_data']['state'],
        ];

        echo "\n• Callback params:";
        echo "\n  - code: " . $callbackParams['code'];
        echo "\n  - restaurant_id: " . $callbackParams['restaurant_id'];
        echo "\n  - state: " . $callbackParams['state'];

        $validatedData = $client->handleCallback($callbackParams, $state);
        echo "\n✓ Callback validated successfully \n";

        $this->assertEquals($callbackParams['code'], $validatedData['code']);
        $this->assertEquals($callbackParams['restaurant_id'], $validatedData['restaurant_id']);
        
        // Test invalid state parameter
        echo "\n• Testing invalid state validation...";
        $callbackParams['state'] = 'invalid_state'; // Change to a mismatched state
        $this->expectException(\Nava\Dinlr\Exception\ApiException::class);
        $client->handleCallback($callbackParams, $state);
        echo "\n• Invalid state parameter tested successfully.";
        echo "\n--------------------------------------------------------------\n\n";
    }

    /**
     * Test handling error response from OAuth server
     */
    public function testOAuthErrorResponse()
    {
        echo "\n\nSTEP 4: Testing OAuth error response handling";
        echo "\n--------------------------------------------------------------";

        $config = new OAuthConfig($this->testConfig);
        $client = new OAuthClient($config);
        $state  = $this->testConfig['test_oauth_data']['state'];

        $callbackParams = [
            'error' => 'access_denied',
            'state' => $state,
        ];

        echo "\n• Error callback params:";
        echo "\n  - error: " . $callbackParams['error'];
        echo "\n  - state: " . $callbackParams['state'];
        echo "\n• Expected exception: ApiException with message 'Authorization was denied: access_denied'";
        echo "\n--------------------------------------------------------------\n\n";
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Authorization was denied: access_denied');

        $client->handleCallback($callbackParams, $state);
    }

    /**
     * Test getting access token and refresh token
     */
    public function testGetAccessToken()
    {
        echo "\n\nSTEP 5: Testing access token retrieval";
        echo "\n--------------------------------------------------------------";

        // Reload config.php to get the latest values written by previous test
        $this->testConfig = require __DIR__ . '/config.php';

        $config = new OAuthConfig($this->testConfig);
        $client = new OAuthClient($config);

        $code          = $this->testConfig['test_oauth_data']['callback']['code'];
        $restaurantId  = $this->testConfig['test_oauth_data']['callback']['restaurant_id'];

        echo "\n• Using code: " . $code;
        echo "\n• Using restaurant ID: " . $restaurantId;
        echo "\n• Retrieving access and refresh tokens for client ID: " . $this->testConfig['client_id'];

        $tokenData = $client->getAccessToken($code, $restaurantId);

        echo "\n✓ Access token retrieved successfully";
        echo "\n• Access Token: " . $tokenData['access_token'];
        echo "\n• Refresh Token: " . $tokenData['refresh_token'];
        echo "\n• Expires In: " . $tokenData['expires_in'] . " seconds";

        echo "\n\n• Saving token data to config.php...";

        if (!isset($tokenData['access_token']) || !isset($tokenData['refresh_token']) || !isset($tokenData['expires_in'])) {
            throw new ApiException('Failed to retrieve access token data');
        } 
        else {
            $this->testConfig['test_oauth_data']['token_response']['access_token'] = $tokenData['access_token'];
            $this->testConfig['test_oauth_data']['token_response']['refresh_token'] = $tokenData['refresh_token'];
            $this->testConfig['test_oauth_data']['token_response']['expires_in'] = $tokenData['expires_in'];

            $this->testConfig['api_key'] = $tokenData['access_token']; 

            $configPath = __DIR__ . '/config.php';
            file_put_contents($configPath, '<?php return ' . var_export($this->testConfig, true) . ';');
            echo "\n• ✅ Saved access and refresh tokens to config.php\n";
        }

        echo "\n• Test access and refresh tokens in config.php:";
        echo "\n• Access Token: " . $this->testConfig['test_oauth_data']['token_response']['access_token'];
        echo "\n• Refresh Token: " . $this->testConfig['test_oauth_data']['token_response']['refresh_token'];
        echo "\n• Expires In: " . $this->testConfig['test_oauth_data']['token_response']['expires_in'] . " seconds";
        echo "\n--------------------------------------------------------------\n\n";

        $this->assertArrayHasKey('access_token', $tokenData);
        $this->assertArrayHasKey('refresh_token', $tokenData);
        $this->assertArrayHasKey('expires_in', $tokenData);
    }
}
