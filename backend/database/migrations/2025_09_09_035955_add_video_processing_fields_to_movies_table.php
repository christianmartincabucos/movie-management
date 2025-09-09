<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->string('thumbnail')->nullable()->after('video_file');
            $table->string('hls_path')->nullable()->after('thumbnail');
            $table->boolean('is_processed')->default(false)->after('hls_path');
            $table->text('processing_error')->nullable()->after('is_processed');
        });
    }

    public function down()
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn(['thumbnail', 'hls_path', 'is_processed', 'processing_error']);
        });
    }
};