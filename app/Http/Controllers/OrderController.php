<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Stock;
use App\Models\StockLog;
use Illuminate\Support\Str;
use App\Models\OrderProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $perPage = $request->get('per_page', 10);

        $query = Order::query();

        if ($q) {
            $query->where(function($qr) use ($q) {
                $qr->where('customer_name', 'like', "%{$q}%")
                   ->orWhere('invoice_number', 'like', "%{$q}%");
            });
        }

        if ($dateFrom) $query->whereDate('date_time', '>=', $dateFrom);
        if ($dateTo) $query->whereDate('date_time', '<=', $dateTo);

        return $query->with('items')->orderBy('date_time','desc')->paginate($perPage);
    }

    public function show($id)
    {
        return Order::with(['items','items.product','items.stock'])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name'      => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.stock_id'   => 'nullable|integer|exists:stocks,id', 
        ]);

        DB::beginTransaction();
        try {
            $invoice = 'INV' . strtoupper(Str::random(6)) . time();
            $order = Order::create([
                'invoice_number' => $invoice,
                'customer_name' => $data['customer_name'] ?? null,
                'status' => 'Pending',
            ]);

            $total = 0;
            foreach ($data['items'] as $it) {
                $need = $it['quantity'];
                $stocksQuery = Stock::where('product_id', $it['product_id'])->where('quantity','>',0)
                    ->orderBy('created_at','asc'); 

                if (!empty($it['stock_id'])) {
                    $preferred = Stock::where('id', $it['stock_id'])->where('quantity','>',0)->first();
                    $stocks = collect();
                    if ($preferred) $stocks->push($preferred);
                    $others = $stocksQuery->where('id','<>', $it['stock_id'])->get();
                    $stocks = $stocks->merge($others);
                } else {
                    $stocks = $stocksQuery->get();
                }

                foreach ($stocks as $stock) {
                    if ($need <= 0) break;
                    $take = min($stock->quantity, $need);
                    if ($take <= 0) continue;

                    $previous = $stock->quantity;
                    $stock->decrement('quantity', $take);
                    $stock->last_update_at = now();
                    $stock->save();

                    StockLog::create([
                        'type' => 'Order-create',
                        'stock_id' => $stock->id,
                        'product_id' => $stock->product_id,
                        'previous_quantity' => $previous,
                        'change_quantity' => -$take,
                        'current_quantity' => $stock->quantity,
                    ]);

                    $sub = $take * $stock->sale_price;
                    $profitPercent = $stock->purchase_price ? (($stock->sale_price - $stock->purchase_price) / max(0.0001, $stock->purchase_price) * 100) : 0;

                    OrderProduct::create([
                        'order_id' => $order->id,
                        'product_id' => $stock->product_id,
                        'stock_id' => $stock->id,
                        'sale_price' => $stock->sale_price,
                        'sub_total' => $sub,
                        'profit_percent' => round($profitPercent,2),
                        'quantity' => $take,
                    ]);

                    $total += $sub;
                    $need -= $take;
                }

                if ($need > 0) {
                    DB::rollBack();
                    return response()->json(['error' => 'Not enough stock for product_id '.$it['product_id']], 400);
                }
            }

            $order->total_amount = $total;
            $order->save();

            DB::commit();
            return response()->json($order->load('items'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error'=>$e->getMessage()], 500);
        }
    }
    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'customer_name'      => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.stock_id'   => 'nullable|integer|exists:stocks,id',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::with('items')->findOrFail($id);
            foreach ($order->items as $op) {
                $stock = Stock::lockForUpdate()->find($op->stock_id);
                if ($stock) {
                    $previous = $stock->quantity;
                    $stock->increment('quantity', $op->quantity);
                    $stock->last_update_at = now();
                    $stock->save();

                    StockLog::create([
                        'type'              => 'Order-update',
                        'stock_id'          => $stock->id,
                        'product_id'        => $stock->product_id,
                        'previous_quantity' => $previous,
                        'change_quantity'   => $op->quantity,
                        'current_quantity'  => $stock->quantity,
                    ]);
                }
            }
            $order->items()->delete();
            $total = 0;
            foreach ($data['items'] as $it) {
                $need = $it['quantity'];

                $stocksQuery = Stock::where('product_id', $it['product_id'])->where('quantity','>',0)
                    ->orderBy('created_at','asc');

                if (!empty($it['stock_id'])) {
                    $preferred = Stock::where('id', $it['stock_id'])->where('quantity','>',0)->first();
                    $stocks = collect();
                    if ($preferred) $stocks->push($preferred);
                    $others = $stocksQuery->where('id','<>', $it['stock_id'])->get();
                    $stocks = $stocks->merge($others);
                } else {
                    $stocks = $stocksQuery->get();
                }

                foreach ($stocks as $stock) {
                    if ($need <= 0) break;
                    $take = min($stock->quantity, $need);
                    if ($take <= 0) continue;

                    $previous = $stock->quantity;
                    $stock->decrement('quantity', $take);
                    $stock->last_update_at = now();
                    $stock->save();

                    StockLog::create([
                        'type'              => 'Order-update',
                        'stock_id'          => $stock->id,
                        'product_id'        => $stock->product_id,
                        'previous_quantity' => $previous,
                        'change_quantity'   => -$take,
                        'current_quantity'  => $stock->quantity,
                    ]);

                    $sub = $take * $stock->sale_price;
                    $profitPercent = $stock->purchase_price ? (($stock->sale_price - $stock->purchase_price) / max(0.0001,$stock->purchase_price) * 100) : 0;

                    OrderProduct::create([
                        'order_id' => $order->id,
                        'product_id' => $stock->product_id,
                        'stock_id' => $stock->id,
                        'sale_price' => $stock->sale_price,
                        'sub_total' => $sub,
                        'profit_percent' => round($profitPercent,2),
                        'quantity' => $take,
                    ]);

                    $total += $sub;
                    $need -= $take;
                }

                if ($need > 0) {
                    DB::rollBack();
                    return response()->json(['error' => 'Not enough stock for product_id '.$it['product_id']], 400);
                }
            }

            $order->customer_name = $data['customer_name'] ?? $order->customer_name;
            $order->total_amount = $total;
            $order->save();

            DB::commit();
            return response()->json($order->load('items'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error'=>$e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $order = Order::with('items')->findOrFail($id);

            foreach ($order->items as $op) {
                $stock = Stock::lockForUpdate()->find($op->stock_id);
                if ($stock) {
                    $previous = $stock->quantity;
                    $stock->increment('quantity', $op->quantity);
                    $stock->last_update_at = now();
                    $stock->save();

                    StockLog::create([
                        'type'              => 'Order-delete',
                        'stock_id'          => $stock->id,
                        'product_id'        => $stock->product_id,
                        'previous_quantity' => $previous,
                        'change_quantity'   => $op->quantity,
                        'current_quantity'  => $stock->quantity,
                    ]);
                }
            }

            $order->delete();

            DB::commit();
            return response()->json(['message'=>'Order deleted']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error'=>$e->getMessage()], 500);
        }
    }
    
}
