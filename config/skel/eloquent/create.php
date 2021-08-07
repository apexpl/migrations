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
        $schema->create('~table_name~', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down($schema)
    {
        $schema->dropIfExists('~table_name~');
    }
}
