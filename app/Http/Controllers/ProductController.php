<?php
/**
 * Senior note:
 * - Enforce vendor ownership; admins override.
 * - Cache invalidation on mutations.
 * - Signed URL generation on-demand to avoid leaking public URLs.
 */

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->with('vendor');

        // Basic search/filter for demonstration; keep input sanitized
        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%$q%")
                  ->orWhere('description', 'like', "%$q%");
            });
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->integer('vendor_id'));
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->float('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->float('max_price'));
        }

        $sort  = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        $query->orderBy($sort, $order);

        return response()->json($query->paginate(10));
    }

    public function show(Product $product, Request $request)
    {
        $data = $product->toArray();

        if ($request->boolean('includeSigned') && $product->image_key) {
            $data['image_signed_url'] = Storage::disk('s3')->temporaryUrl(
                $product->image_key,
                now()->addMinutes(10)
            );
        }

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'vendor' && $user->role !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:2000'],
            'price'       => ['required', 'numeric', 'min:0'],
            'stock'       => ['required', 'integer', 'min:0'],
            'image'       => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        // Store with UUID key to avoid collisions
        $uuid = (string) Str::uuid();
        $ext  = $request->file('image')->extension();
        $key  = "products/{$uuid}.{$ext}";

        // Storage::disk('s3')->put($key, file_get_contents($request->file('image')));

        $request->file('image')->storeAs('products', "{$uuid}.{$ext}", 's3');

        $product = Product::create([
            'name'        => $data['name'],
            'description' => $data['description'],
            'price'       => $data['price'],
            'stock'       => $data['stock'],
            'image_key'   => $key,
            'vendor_id'   => $user->id,
        ]);

        Cache::forget('popular_products');

        return response()->json($product, 201);
    }

    public function update(Product $product, Request $request)
    {
        $user = $request->user();
        if (!($user->role === 'admin' || $product->vendor_id === $user->id)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:150'],
            'description' => ['sometimes', 'string', 'max:2000'],
            'price'       => ['sometimes', 'numeric', 'min:0'],
            'stock'       => ['sometimes', 'integer', 'min:0'],
            'image'       => ['sometimes', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        if ($request->hasFile('image')) {
            $uuid = (string) Str::uuid();
            $ext  = $request->file('image')->extension();
            $key  = "products/{$uuid}.{$ext}";
            Storage::disk('s3')->put($key, file_get_contents($request->file('image')));
            $data['image_key'] = $key;
        }

        $product->update($data);

        Cache::forget('popular_products');

        return response()->json($product);
    }

    public function destroy(Product $product, Request $request)
    {
        $user = $request->user();
        if (!($user->role === 'admin' || $product->vendor_id === $user->id)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $product->delete();

        Cache::forget('popular_products');

        return response()->json([], 204);
    }

    public function popular()
    {
        // Cache TTL: 1 hour; invalidated on order/product mutations
        $products = Cache::remember('popular_products', 3600, function () {
            return Product::withCount('orders')
                ->orderBy('orders_count', 'desc')
                ->take(10)
                ->get();
        });

        return response()->json($products);
    }

    public function imageSignedUrl(Product $product)
    {
        if (!$product->image_key) {
            return response()->json(['error' => 'No image'], 404);
        }

        try {
            // Check if file exists before generating signed URL
            if (!Storage::disk('s3')->exists($product->image_key)) {
                return response()->json(['error' => 'Image not found in S3'], 404);
            }

            $url = Storage::disk('s3')->temporaryUrl(
                $product->image_key,
                now()->addMinutes(10)
            );

            return response()->json(['signed_url' => $url]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate signed URL',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
