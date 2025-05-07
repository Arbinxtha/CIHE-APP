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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
             $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('cascade');
             $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
             // For individual messages, receiver_id is used. For group messages, group_id is used.
             $table->foreignId('receiver_id')->nullable()->constrained('users')->onDelete('cascade');
             $table->foreignId('group_id')->nullable()->constrained('groups')->onDelete('cascade');
             $table->text('content');
            //  subject 
        });
    }


    // No group  message individual message

    /**
     * campus cihe
     * Course type bachelor
     * subject courses:
     * 
     */

     /**
      * Admin
      * 0001
      */
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
