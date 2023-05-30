<?php

declare(strict_types=1);

namespace Spanjaan\BlogHub\Updates;

use Schema;
use Winter\Storm\Database\Schema\Blueprint;
use Winter\Storm\Database\Updates\Migration;
use System\Classes\PluginManager;

/**
 * CreateVisitorsTable Migration
 */
class CreateVisitorsTable extends Migration
{
    /**
     * @inheritDoc
     */
    public function up()
    {
        if (!PluginManager::instance()->hasPlugin('Winter.Blog')) {
            return;
        }

        Schema::create('spanjaan_bloghub_visitors', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->string('user', 64);
            $table->json('posts')->nullable();
            $table->text('likes')->nullable();
            $table->text('dislikes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        Schema::dropIfExists('spanjaan_bloghub_visitors');
    }

}
