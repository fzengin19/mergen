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
        Schema::table('research', function (Blueprint $table) {
            $table->string('target_audience')->nullable()->after('additional_info');
            $table->string('content_type')->nullable()->after('target_audience');
            $table->string('tone')->nullable()->after('content_type');
            $table->json('hashtags')->nullable()->after('tone');
            $table->json('trend_data')->nullable()->after('hashtags');
            $table->json('competitor_data')->nullable()->after('trend_data');
            $table->json('content_ideas')->nullable()->after('competitor_data');
            $table->json('generated_content')->nullable()->after('content_ideas');
            $table->json('optimized_content')->nullable()->after('generated_content');
            $table->string('status')->default('pending')->after('optimized_content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('research', function (Blueprint $table) {
            $table->dropColumn([
                'target_audience',
                'content_type', 
                'tone',
                'hashtags',
                'trend_data',
                'competitor_data',
                'content_ideas',
                'generated_content',
                'optimized_content',
                'status'
            ]);
        });
    }
};
