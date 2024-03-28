<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_files', function (Blueprint $table) {
            $table->id();
            $table->string('path')->index();
            $table->string('original_name')->nullable()->index();
            $table->string('disk')->index();
            $table->unsignedBigInteger('user_id')?->index()?->nullable();
            $table->boolean('public')->default(false)->index();
            $table->timestamp('expires_in')->nullable()->index();
            $table->timestamps();

            $table->foreign('user_id')->references('id')
                ->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_files');
    }
};
