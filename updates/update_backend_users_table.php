<?php

declare(strict_types=1);

namespace Spanjaan\BlogHub\Updates;

use Schema;
use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use System\Classes\PluginManager;

/**
 * UpdateBackendUsers Migration
 */
class UpdateBackendUsersTable extends Migration
{
    /**
     * @inheritDoc
     */
    public function up()
    {
        Schema::table('backend_users', function (Blueprint $table) {
            $table->string('spanjaan_bloghub_display_name', 128)->nullable();
            $table->string('spanjaan_bloghub_author_slug', 128)->unique()->nullable();
            $table->text('spanjaan_bloghub_about_me')->nullable();
        });
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        if (method_exists(Schema::class, 'dropColumns')) {
            Schema::dropColumns('backend_users', ['spanjaan_bloghub_display_name', 'spanjaan_bloghub_author_slug', 'spanjaan_bloghub_about_me']);
        } else {
            Schema::table('backend_users', function (Blueprint $table) {
                if (Schema::hasColumn('backend_users', 'spanjaan_bloghub_display_name')) {
                    $table->dropColumn('spanjaan_bloghub_display_name');
                }
                if (Schema::hasColumn('backend_users', 'spanjaan_bloghub_author_slug')) {
                    $table->dropColumn('spanjaan_bloghub_author_slug');
                }
                if (Schema::hasColumn('backend_users', 'spanjaan_bloghub_about_me')) {
                    $table->dropColumn('spanjaan_bloghub_about_me');
                }
            });
        }
    }
}
