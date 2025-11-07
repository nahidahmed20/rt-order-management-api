<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function productSearch(Request $request)
    {
        $q = $request->query('q', '');

        $products = Product::query()
            ->where('name', 'like', "%{$q}%")
            ->orWhere('barcode', 'like', "%{$q}%")
            ->get();

        $result = [];

        foreach ($products as $product) {
            $stock = Stock::where('product_id', $product->id)
                ->where('quantity', '>', 0)
                ->orderBy('id', 'asc') 
                ->first();

            if ($stock) {
                $result[] = [
                    'product_id' => $product->id,
                    'stock_id' => $stock->id,
                    'name' => $product->name,
                    'sku' => $stock->sku,
                    'quantity' => $stock->quantity,
                    'sale_price' => $stock->sale_price,
                ];
            }
        }

        return response()->json($result);
    }
    public function index() {
        return Product::with('stocks')->paginate(20);
    }

    public function show($id) {
        return Product::with('stocks')->findOrFail($id);
    }
}
