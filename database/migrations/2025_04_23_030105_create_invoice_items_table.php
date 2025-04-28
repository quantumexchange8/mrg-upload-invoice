<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('doc_no')->nullable();
            $table->date('doc_date')->nullable();
            $table->string('code')->nullable();
            $table->string('description_hdr')->nullable();
            $table->integer('seq')->nullable();
            $table->string('description_dtl')->nullable();
            $table->integer('qty')->nullable();
            $table->string('uom')->nullable();
            $table->decimal('unit_price', 13, 2)->nullable();
            $table->decimal('amount', 13, 2)->nullable();
            $table->string('item_code')->nullable();
            $table->string('account')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')
                  ->references('id')
                  ->on('invoices')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
