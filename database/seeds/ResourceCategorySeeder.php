<?php

use App\ResourceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ResourceCategorySeeder extends Seeder
{
    private const CATEGORIES = [
        'Fengselsbetjentrollen og profesjonsutøvelse',
        'Miljøarbeid, relasjoner og kommunikasjon',
        'Psykisk helse, rus og selvmordsforebygging',
        'Isolasjon, tvang og menneskerettigheter',
        'Sikkerhet og beredskap',
        'Tilbakeføring og endringsarbeid',
        'Barn, unge, familier og pårørende',
        'Lovverk, regler og offentlige veiledere',
        'Forskning, rapporter og statistikk',
        'Arbeidsmiljø og ivaretakelse av ansatte',
    ];

    public function run()
    {
        $oldCategory = ResourceCategory::where('slug', 'barn-unge-og-familier')
            ->orWhere('name', 'Barn, unge og familier')
            ->first();
        $newCategory = ResourceCategory::where('slug', Str::slug('Barn, unge, familier og pårørende'))
            ->orWhere('name', 'Barn, unge, familier og pårørende')
            ->first();

        if ($oldCategory && $newCategory && $oldCategory->id !== $newCategory->id) {
            $newCategory->professionalResources()->update(['category_id' => $oldCategory->id]);
            $newCategory->delete();
        }

        if ($oldCategory) {
            $oldCategory->update([
                'name' => 'Barn, unge, familier og pårørende',
                'slug' => Str::slug('Barn, unge, familier og pårørende'),
            ]);
        }

        foreach (self::CATEGORIES as $index => $name) {
            ResourceCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                ]
            );
        }
    }
}
