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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('password');
            $table->string('avatar_url')->nullable()->after('is_active');
            $table->string('theme')->default('soft')->after('avatar_url');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'is_active',
                'avatar_url',
                'theme',
                'last_login_at',
            ]);
        });
    }
};
