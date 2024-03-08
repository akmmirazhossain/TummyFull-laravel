<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   // database/migrations/xxxx_xx_xx_create_meals_table.php

public function up()
{
    Schema::create('mrd_menu', function (Blueprint $table) {
        $table->id('mrd_menu_id');
        $table->string('mrd_menu_day');
        $table->string('mrd_menu_food_id');
        $table->string('mrd_menu_period');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};
