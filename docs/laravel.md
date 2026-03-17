# Laravel Integration

## Installation

Auto-discovered on Laravel 5.5+. For earlier versions, register manually in `config/app.php`:

```php
'providers' => [
    Nava\Dinlr\Laravel\DinlrServiceProvider::class,
],

'aliases' => [
    'Dinlr' => Nava\Dinlr\Laravel\Facades\Dinlr::class,
],
```

Publish the config file:

```bash
php artisan vendor:publish --provider="Nava\Dinlr\Laravel\DinlrServiceProvider"
```

## Environment Variables

```
DINLR_API_KEY=your_api_key
DINLR_RESTAURANT_ID=your_restaurant_id
DINLR_API_URL=https://api.dinlr.com/v1
DINLR_TIMEOUT=30
DINLR_DEBUG=false
```

## Usage

```php
use Dinlr;

class OrderController extends Controller
{
    public function index()
    {
        $restaurant = Dinlr::restaurant()->get();
        $customers  = Dinlr::customers()->list();

        return view('orders.index', compact('restaurant', 'customers'));
    }

    public function placeOrder(Request $request)
    {
        $cartData = [
            'location'   => $request->location_id,
            'items'      => $request->items,
            'order_info' => [
                'order_no' => 'WEB' . time(),
                'customer' => $request->customer_id,
                'notes'    => $request->notes,
            ],
        ];

        try {
            $order = Dinlr::cart()->submit($cartData);

            return response()->json([
                'success'      => true,
                'order_id'     => $order->getId(),
                'order_number' => $order->getOrderNumber(),
                'total'        => $order->getTotal(),
            ]);
        } catch (\Nava\Dinlr\Exception\ApiException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }
}
```

## OAuth for Laravel

Publish the OAuth config:

```bash
php artisan vendor:publish --provider="Nava\Dinlr\Laravel\OAuthServiceProvider"
```

Add OAuth settings to `.env`:

```
DINLR_CLIENT_ID=your_client_id
DINLR_CLIENT_SECRET=your_client_secret
DINLR_REDIRECT_URI=https://yourapp.com/oauth/callback
```

Example OAuth controller:

```php
use Nava\Dinlr\Laravel\Facades\DinlrOAuth;

class OAuthController extends Controller
{
    public function authorize()
    {
        $state = Str::random(40);
        session(['oauth_state' => $state]);

        return redirect(DinlrOAuth::getAuthorizationUrl($state));
    }

    public function callback(Request $request)
    {
        try {
            $callbackData = DinlrOAuth::handleCallback($request->all(), session('oauth_state'));
            $tokens       = DinlrOAuth::getAccessToken($callbackData['code'], $callbackData['restaurant_id']);

            session([
                'dinlr_access_token'  => $tokens['access_token'],
                'dinlr_refresh_token' => $tokens['refresh_token'],
                'dinlr_restaurant_id' => $callbackData['restaurant_id'],
            ]);

            return redirect()->route('dashboard')->with('success', 'Connected to Dinlr!');

        } catch (\Nava\Dinlr\Exception\ApiException $e) {
            return redirect()->route('oauth.authorize')->with('error', 'OAuth failed: ' . $e->getMessage());
        }
    }
}
```
