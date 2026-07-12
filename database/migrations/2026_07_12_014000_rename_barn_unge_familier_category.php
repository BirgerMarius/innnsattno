<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RenameBarnUngeFamilierCategory extends Migration
{
    private const OLD_NAME = 'Barn, unge og familier';
    private const OLD_SLUG = 'barn-unge-og-familier';
    private const NEW_NAME = 'Barn, unge, familier og pårørende';
    private const NEW_SLUG = 'barn-unge-familier-og-parorende';

    public function up()
    {
        DB::transaction(function () {
            $oldCategory = DB::table('resource_categories')
                ->where('slug', self::OLD_SLUG)
                ->orWhere('name', self::OLD_NAME)
                ->first();

            $newCategory = DB::table('resource_categories')
                ->where('slug', self::NEW_SLUG)
                ->orWhere('name', self::NEW_NAME)
                ->first();

            if ($oldCategory && $newCategory && $oldCategory->id !== $newCategory->id) {
                DB::table('professional_resources')
                    ->where('category_id', $newCategory->id)
                    ->update(['category_id' => $oldCategory->id]);

                DB::table('resource_categories')
                    ->where('id', $newCategory->id)
                    ->delete();
            }

            if ($oldCategory) {
                DB::table('resource_categories')
                    ->where('id', $oldCategory->id)
                    ->update([
                        'name' => self::NEW_NAME,
                        'slug' => self::NEW_SLUG,
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    public function down()
    {
        DB::table('resource_categories')
            ->where('slug', self::NEW_SLUG)
            ->orWhere('name', self::NEW_NAME)
            ->update([
                'name' => self::OLD_NAME,
                'slug' => self::OLD_SLUG,
                'updated_at' => now(),
            ]);
    }
}
