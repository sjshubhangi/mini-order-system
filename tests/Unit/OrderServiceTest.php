<?php
/**
 * Senior note:
 * - RefreshDatabase ensures isolation; validate atomic stock behavior through endpoints.
 */

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_decrements_on_order()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $vendor   = User::factory()->create(['role' => 'vendor']);
        $product  = Product::factory()->create(['vendor_id' => $vendor->id, 'stock' => 10, 'price' => 100]);

        Passport::actingAs($customer);

        $resp = $this->postJson('/api/orders', ['product_id' => $product->id, 'quantity' => 3]);

        $resp->assertStatus(201);
        $this->assertEquals(7, $product->fresh()->stock);
    }
}
