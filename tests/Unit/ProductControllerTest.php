<?php
/**
 * Senior note:
 * - Storage::fake('s3') isolates external dependency for repeatable tests.
 */

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Laravel\Passport\Passport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductControllerTest extends TestCase
{
    public function test_vendor_can_create_product()
    {
        Storage::fake('s3');

        $vendor = User::factory()->create(['role' => 'vendor']);
        Passport::actingAs($vendor);

        $response = $this->postJson('/api/products', [
            'name' => 'Test Product',
            'description' => 'Desc',
            'price' => 100.50,
            'stock' => 5,
            'image' => UploadedFile::fake()->image('pic.jpg'),
        ]);

        $response->assertStatus(201)->assertJsonStructure(['id','name','image_key','vendor_id']);
    }
}
