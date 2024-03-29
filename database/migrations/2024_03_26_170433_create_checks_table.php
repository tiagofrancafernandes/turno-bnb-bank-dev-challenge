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
        Schema::create('checks', function (Blueprint $table) {
            $table->id();
            $table->string('title')->index();
            $table->string('amount')->index();
            $table->integer('status')->index()->default(App\Enums\CheckStatus::CREATED?->value);
            $table->unsignedBigInteger('check_image_file_id')?->nullable();
            $table->unsignedBigInteger('account_id')->index();
            $table->timestamps();
            $table->index('created_at');
            $table->index('updated_at');

            $table->foreign('check_image_file_id')->references('id')->on('app_files');
            $table->foreign('account_id')->references('id')->on('accounts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checks');
    }
};
