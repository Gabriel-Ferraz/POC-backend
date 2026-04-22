<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add index to plans.slug for faster lookups
        Schema::table("plans", function (Blueprint $table) {
            $table->index("slug");
        });

        // Add index to user_subscriptions.expires_at for expiration checks
        Schema::table("user_subscriptions", function (Blueprint $table) {
            $table->index("expires_at");
        });

        // Add compound index for subscription queries
        Schema::table("user_subscriptions", function (Blueprint $table) {
            $table->index(["plan_id", "status", "expires_at"]);
        });

        // Add index to users.is_active for active user queries
        Schema::table("users", function (Blueprint $table) {
            $table->index("is_active");
        });
    }

    public function down(): void
    {
        Schema::table("plans", function (Blueprint $table) {
            $table->dropIndex(["slug"]);
        });

        Schema::table("user_subscriptions", function (Blueprint $table) {
            $table->dropIndex(["expires_at"]);
            $table->dropIndex(["plan_id", "status", "expires_at"]);
        });

        Schema::table("users", function (Blueprint $table) {
            $table->dropIndex(["is_active"]);
        });
    }
};
