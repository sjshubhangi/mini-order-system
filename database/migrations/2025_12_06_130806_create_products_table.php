<?php
/**
 * Senior note:
 * - Soft deletes allow safe removal and audit.
 * - Store S3 object key (image_key) instead of public URL for signed access.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('stock');
            $table->string('image_key')->nullable(); 
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
