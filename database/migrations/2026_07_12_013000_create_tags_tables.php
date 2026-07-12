<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTagsTables extends Migration
{
    public function up()
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->timestamps();
        });

        Schema::create('professional_resource_tag', function (Blueprint $table) {
            $table->foreignId('professional_resource_id')
                ->constrained('professional_resources')
                ->cascadeOnDelete();
            $table->foreignId('tag_id')
                ->constrained('tags')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['professional_resource_id', 'tag_id'], 'professional_resource_tag_pk');
            $table->index('tag_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('professional_resource_tag');
        Schema::dropIfExists('tags');
    }
}
