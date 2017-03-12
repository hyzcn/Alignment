<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddResourceFieldsToSettingsTable extends Migration {

    /**
     * Make changes to the table.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function(Blueprint $table) {

            $table->string('resource_file_name')->nullable();
            $table->integer('resource_file_size')->nullable()->after('resource_file_name');
            $table->string('resource_content_type')->nullable()->after('resource_file_size');
            $table->timestamp('resource_updated_at')->nullable()->after('resource_content_type');

        });

    }

    /**
     * Revert the changes to the table.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function(Blueprint $table) {

            $table->dropColumn('resource_file_name');
            $table->dropColumn('resource_file_size');
            $table->dropColumn('resource_content_type');
            $table->dropColumn('resource_updated_at');

        });
    }

}