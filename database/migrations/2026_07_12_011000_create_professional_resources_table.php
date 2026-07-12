<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfessionalResourcesTable extends Migration
{
    public function up()
    {
        Schema::create('professional_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('resource_categories')
                ->restrictOnDelete();
            $table->string('title');
            $table->string('url', 2048);
            $table->text('comment')->nullable();
            $table->string('publisher')->nullable();
            $table->string('content_type')->nullable();
            $table->unsignedSmallInteger('publication_year')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->string('status', 20)->default('draft')->index();
            $table->integer('sort_order')->default(0)->index();
            $table->date('last_checked_at')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();

            $table->index(['category_id', 'status', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('professional_resources');
    }
}
