<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('admission_number')->unique();
            $table->foreignId('class_id')->constrained()->restrictOnDelete();
            $table->string('parent_name');
            $table->string('parent_phone');
            $table->string('gender', 20);
            $table->date('dob');
            $table->timestamps();
            $table->index(['class_id', 'admission_number']);
            $table->index('parent_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
