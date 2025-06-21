<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OpenFoodFacts\Laravel\Facades\OpenFoodFacts;
use OpenFoodFacts\Laravel\Facades\OpenBeautyFacts;
use OpenFoodFacts\Exception\ProductNotFoundException;
use Exception;
use App\Http\Controllers\Api\ProdutoController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class OpenFoodFactsController extends Controller
{
    /**
     * The ProdutoController instance.
     *
     * @var \App\Http\Controllers\Api\ProdutoController
     */
    protected $produtoController;

    /**
     * Get product information by barcode from Open Food Facts.
     *
     * @param string $barcode
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * Create a new controller instance.
     * Inject ProdutoController via the constructor.
     *
     * @param \App\Http\Controllers\Api\ProdutoController $produtoController
     * @return void
     */
    public function __construct(ProdutoController $produtoController) // Inject via constructor
    {
        $this->produtoController = $produtoController; // Assign to property
    }

    public function getProductByBarcode(string $barcode)
    {
        try {
            if (!ctype_digit($barcode)) {
                return response()->json(['message' => 'Código de barras inválido. Deve conter apenas dígitos.'], 400);
            }

            $client = new Client([
            'base_uri' => 'https://br.openfoodfacts.org/api/v0/', // Or your specific endpoint
            'timeout'  => 10.0, // Timeout for the entire request in seconds
            'connect_timeout' => 5.0, // Timeout for attempting to connect to the server
            ]);

            // $response = $client->request('GET', "product/{$barcode}.json");
            // $productData = json_decode($response->getBody()->getContents(), true);

            $productData = OpenFoodFacts::barcode($barcode);
            // $productData = OpenBeautyFacts::barcode($barcode);

            if (is_null($productData)) {
                return response()->json(['message' => 'Produto não encontrado na base de dados Open Food Facts.'], 404);
            }

            $productDataArray = json_decode(json_encode($productData), true);

            $simplifiedData = [
                'code' => $productDataArray['code'] ?? $barcode,
                'product_name_pt' => $productDataArray['product_name_pt'] ?? $productDataArray['product_name'] ?? null,
                'generic_name_pt' => $productDataArray['generic_name_pt'] ?? $productDataArray['generic_name'] ?? null,
                'brands' => $productDataArray['brands'] ?? null,
                'quantity' => $productDataArray['quantity'] ?? null,
                'categories_tags' => $productDataArray['categories_tags'] ?? [],
                'ingredients_text_pt' => $productDataArray['ingredients_text_pt'] ?? $productDataArray['ingredients_text'] ?? null,
                'image_url' => $productDataArray['image_url'] ?? null,
                'image_front_url' => $productDataArray['image_front_url'] ?? null,

            ]; 

            $simplifiedData = array_filter($simplifiedData, function ($value) {
                 return !is_null($value);
            });

            return $this->produtoController->storeOrUpdateOpenFoodFacts($simplifiedData); //
        } catch (ProductNotFoundException $e) {
            return response()->json(['message' => 'Produto não encontrado na base de dados Open Food Facts.'], 404);
        } catch (Exception $e) {
            Log::error('Erro ao buscar produto no Open Food Facts: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao conectar com a API Open Food Facts.'], 500);
        }
    }

    /**
     * Search for products by search terms on Open Food Facts.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchProducts(Request $request)
    {
        // Validate that a search term is provided
        $searchTerm = $request->query('term'); // Get 'term' from query string (e.g., /search?term=chocolate)
        if (empty($searchTerm)) {
            return response()->json(['message' => 'Parâmetro de busca "term" é obrigatório.'], 400);
        }

        // Get optional pagination parameters
        $page = $request->query('page', 1); // Default to page 1
        $pageSize = $request->query('pageSize', 20); // Default page size

        try {
            // Perform the search using the facade
            $searchResults = OpenFoodFacts::find($searchTerm, $page, $pageSize);

            // Return the search results
            // The result structure might include pagination info and a list of products
            return response()->json($searchResults);
        } catch (Exception $e) {
            // Catch any errors during the API call
            Log::error('Erro ao buscar produtos no Open Food Facts: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao conectar com a API Open Food Facts.'], 500);
        }
    }

    // You can add more methods here to utilize other features of the package,
    // like fetching by facet, getting nutrients, etc.
    // Refer to the openfoodfacts/openfoodfacts-php documentation for available methods.
}
