<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('file_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('original_filename');
            $table->integer('total_chunks');
            $table->integer('chunks_received')->default(0);
            $table->string('status'); // 'uploading', 'completed', 'failed'
            $table->string('final_path')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('file_uploads');
    }
};