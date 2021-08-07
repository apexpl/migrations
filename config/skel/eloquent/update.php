<?php

namespace ~namespace~\Eloquent;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class ~class_name~ extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up($schema)
    {
        $schema->table('~table_name~', function (Blueprint $table) {
            //
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down($schema)
    {
        $schema->table('~table_name~', function (Blueprint $table) {
            //
        });
    }
}
