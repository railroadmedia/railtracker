<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeExceptionCodeFromIntToString extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tablePrefix = config('railtracker.table_prefix') ?? 'railtracker3_';

        // delete foreign key constraint

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){
            $table->dropForeign($tablePrefix . 'requests_exception_code_foreign');
        });

        // delete indexes

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){
            $table->dropIndex($tablePrefix . 'requests_exception_code_index');
        });

        Schema::table($tablePrefix . 'exception_codes', function (Blueprint $table) use ($tablePrefix){
            $table->dropIndex('exception_code_index');
        });

        // changes

        Schema::table($tablePrefix . 'exception_codes', function (Blueprint $table) {
            $table->string('exception_code')->unique('exception_code_index')->change();
        });

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) {
            $table->string('exception_code')->index()->nullable()->change();
        });

        // re-create foreign key

        Schema::table(
            $tablePrefix . 'requests',
            function (Blueprint $table) use ($tablePrefix){
                $table
                    ->foreign('exception_code')
                    ->references('exception_code')
                    ->on($tablePrefix . 'exception_codes');
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tablePrefix = config('railtracker.table_prefix') ?? 'railtracker3_';

        // delete foreign key constraint

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){
            $table->dropForeign($tablePrefix . 'requests_exception_code_foreign');
        });

        // delete indexes

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) use ($tablePrefix){
            $table->dropIndex($tablePrefix . 'requests_exception_code_index');
        });

        Schema::table($tablePrefix . 'exception_codes', function (Blueprint $table) use ($tablePrefix){
            $table->dropIndex('exception_code_index');
        });

        // changes

        Schema::table($tablePrefix . 'exception_codes', function (Blueprint $table) {
            $table->unsignedInteger('exception_code', false, true)->unique('exception_code_index')->change();
        });

        Schema::table($tablePrefix . 'requests', function (Blueprint $table) {
            $table->unsignedInteger('exception_code', false, true)->index()->nullable()->change();
        });

        // re-create foreign key

        Schema::table(
            $tablePrefix . 'requests',
            function (Blueprint $table) use ($tablePrefix){
                $table
                    ->foreign('exception_code')
                    ->references('exception_code')
                    ->on($tablePrefix . 'exception_codes');
            }
        );
    }
}
