<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("user_subscriptions", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained()->onDelete("cascade");
            $table->foreignId("plan_id")->constrained()->onDelete("cascade");
            $table->enum("status", ["active", "inactive", "cancelled", "expired"])->default("active");
            $table->timestamp("started_at")->nullable();
            $table->timestamp("expires_at")->nullable();
            $table->timestamps();

            $table->index(["user_id", "status"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("user_subscriptions");
    }
};
