<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Only needed on MySQL, for databases that ran the original
     * create_sales_table migration before 'website_order' was added to its
     * enum list. A fresh install (e.g. the sqlite test database, rebuilt
     * from all migrations on every run) already gets the full enum from
     * that migration, so this is a no-op there.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sales MODIFY source ENUM('pos', 'wine_reservation', 'competition_entry', 'website_order') NOT NULL DEFAULT 'pos'");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sales MODIFY source ENUM('pos', 'wine_reservation', 'competition_entry') NOT NULL DEFAULT 'pos'");
        }
    }
};
