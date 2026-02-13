<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('charge_id');
            $table->string('transaction_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('cad');
            $table->string('postal_code')->nullable();
            $table->string('status')->default('pending');
            $table->string('payment_method')->default('stripe');
            $table->json('metadata')->nullable();
            $table->json('stripe_response')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('charge_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};