<?php
// app/Http/Controllers/ShopifyController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\ShopifyShop;

class ShopifyController extends Controller
{
    protected $apiKey = 'c240be305c76decd9d2535670e8e555a';
    protected $apiSecret = '4f8788194f103ff13161e48ec9df4ef0';
    protected $scopes = 'read_orders,read_products';

    public function redirectToShopify(Request $request)
    {
        $shop = $request->get('shop');
        $redirectUri = route('shopify.callback');
        $state = uniqid();

        $installUrl = "https://{$shop}/admin/oauth/authorize?" . http_build_query([
            'client_id' => $this->apiKey,
            'scope' => $this->scopes,
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);

        return redirect()->away($installUrl);
    }

    public function getAccessToken(Request $request)
    {
        $shop = $request->get('shop');
        $code = $request->get('code');
    
        if (!$shop || !$code) {
            return response()->json(['error' => 'Missing shop or code'], 422);
        }
    
        $response = Http::post("https://{$shop}/admin/oauth/access_token", [
            'client_id' => 'c240be305c76decd9d2535670e8e555a',
            'client_secret' => '4f8788194f103ff13161e48ec9df4ef0',
            'code' => $code,
        ]);
    
        if ($response->successful()) {
            $data = $response->json();
            dd($data); 
        }
    
        return response()->json(['error' => 'Token fetch failed'], 400);
    }
    

    public function getOrders(Request $request)
{
    $shop = $request->get('shop');

    // Hardcoded access token
    $accessToken = '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';

    // Shopify Orders API endpoint
    $url = "https://{$shop}/admin/api/2023-07/orders.json?limit=10";

    // Send request with token
    $response = Http::withHeaders([
        'X-Shopify-Access-Token' => $accessToken
    ])->get($url);

    if ($response->successful()) {
        $orders = $response->json()['orders'];
        return $orders;
    }

    return response()->json([
        'error' => 'Failed to fetch orders',
        'details' => $response->json()
    ], 400);
}

}