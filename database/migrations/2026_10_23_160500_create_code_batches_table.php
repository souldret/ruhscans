<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_batches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('duration_days');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_batches');
    }
};
