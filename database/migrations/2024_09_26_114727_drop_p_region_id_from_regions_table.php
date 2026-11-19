<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropPRegionIdFromRegionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('regions', function (Blueprint $table) {
            // Drop the p_region_id column if it exists
            if (Schema::hasColumn('regions', 'p_region_id')) {
             
                $table->dropColumn('p_region_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('regions', function (Blueprint $table) {
            // Add the p_region_id column back (optional)
            $table->unsignedBigInteger('p_region_id')->nullable()->after('name');
            $table->foreign('p_region_id')->references('id')->on('regions')->onDelete('cascade');
        });
    }
}
