<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained()->cascadeOnDelete();
            $table->string('term', 20);
            $table->unsignedSmallInteger('academic_year');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
            $table->unique(['class_id', 'term', 'academic_year']);
        });

        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('term', 20);
            $table->unsignedSmallInteger('academic_year');
            $table->string('payment_method', 20);
            $table->string('mpesa_code', 30)->nullable()->unique();
            $table->date('paid_at');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['student_id', 'term', 'academic_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_payments');
        Schema::dropIfExists('fee_structures');
    }
};
