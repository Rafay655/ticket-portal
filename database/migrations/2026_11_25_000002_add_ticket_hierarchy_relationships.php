<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing records remain unassigned until an administrator maps them.
        Schema::table('ticket_categories', function (Blueprint $table) {
            $table->foreignId('ticket_type_id')->nullable()->constrained('ticket_types')->restrictOnDelete();
        });
        Schema::table('ticket_sub_types', function (Blueprint $table) {
            $table->foreignId('ticket_category_id')->nullable()->constrained('ticket_categories')->restrictOnDelete();
        });
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('ticket_category_id')->nullable()->constrained('ticket_categories')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', fn (Blueprint $table) => $table->dropConstrainedForeignId('ticket_category_id'));
        Schema::table('ticket_sub_types', fn (Blueprint $table) => $table->dropConstrainedForeignId('ticket_category_id'));
        Schema::table('ticket_categories', fn (Blueprint $table) => $table->dropConstrainedForeignId('ticket_type_id'));
    }
};
