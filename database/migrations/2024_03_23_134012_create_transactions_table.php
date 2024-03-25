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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('type')->index();
            $table->string('amount')->index();
            $table->unsignedBigInteger('account_id')->index();
            $table->boolean('success')->index();
            $table->datetime('performed_on')->index()->nullable();
            $table->longText('notice')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')
                ->on('accounts')->onDelete('cascade');

            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
