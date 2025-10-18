<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('cpf', 11)->unique();
            $table->string('city', 100);
            $table->char('state', 2);
            $table->foreignId('manager_id')
                ->constrained('managers')
                ->onDelete('cascade');
            $table->timestamps();

            $table->index('manager_id');
            $table->index('email');
            $table->index('cpf');
            $table->index(['manager_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
