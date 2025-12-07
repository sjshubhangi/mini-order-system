<?php
/**
 * Senior note:
 * - Use DB transactions and SELECT FOR UPDATE to prevent overselling.
 * - Dispatch async job after commit to avoid duplicate notifications on rollback.
 */

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Jobs\SendOrderNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        if ($request->user()->role !== 'customer') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1'],
        ]);

        $order = DB::transaction(function () use ($data, $request) {
            $product = Product::lockForUpdate()->find($data['product_id']);

            if ($product->stock < $data['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => ['Insufficient stock'],
                ]);
            }

            $product->decrement('stock', $data['quantity']);

            return Order::create([
                'product_id'  => $product->id,
                'customer_id' => $request->user()->id,
                'quantity'    => $data['quantity'],
                'status'      => 'pending',
            ]);
        });

        Cache::forget('popular_products');

        SendOrderNotification::dispatch($order);

        return response()->json($order, 201);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return response()->json(
                Order::with(['product.vendor', 'customer'])->paginate(10)
            );
        }

        if ($user->role === 'vendor') {
            $orders = Order::with(['product.vendor', 'customer'])
                ->whereHas('product', fn($q) => $q->where('vendor_id', $user->id))
                ->paginate(10);

            return response()->json($orders);
        }

        return response()->json(['error' => 'Forbidden'], 403);
    }

    public function show(Order $order, Request $request)
    {
        $user = $request->user();
        if ($user->role === 'admin' ||
            ($user->role === 'vendor' && $order->product->vendor_id === $user->id)) {
            return response()->json($order->load(['product.vendor', 'customer']));
        }
        return response()->json(['error' => 'Forbidden'], 403);
    }

    public function updateStatus(Order $order, Request $request)
    {
        $user = $request->user();
        if (!($user->role === 'admin' ||
              ($user->role === 'vendor' && $order->product->vendor_id === $user->id))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'status' => ['required', 'in:pending,completed'],
        ]);

        $order->update(['status' => $data['status']]);

        return response()->json($order);
    }
}
