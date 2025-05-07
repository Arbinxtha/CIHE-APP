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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
             $table->foreignId('assignment_id')->constrained('assignments')->onDelete('cascade');
             $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
             $table->text('content')->nullable();
             $table->string('file_url')->nullable();
             $table->enum('status', ['submitted', 'approved', 'rejected'])->default('submitted');
             $table->text('feedback')->nullable();
             $table->timestamps(); // submission_date is represented by created_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
