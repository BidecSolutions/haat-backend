<?php

use Illuminate\Database\Migrations\Migration;

// This migration was misdated (2025-03-12) but depended on tables created in
// 2025-11-26. The actual logic has been moved to 2025_12_03_000000_add_bangla_to_feedback_tables.php.
// This file is kept as a no-op to avoid breaking existing deployments that
// have already recorded it in the migrations table.
return new class extends Migration
{
    public function up(): void {}
    public function down(): void {}
};
