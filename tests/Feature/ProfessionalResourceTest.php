<?php

namespace Tests\Feature;

use App\ProfessionalResource;
use App\ResourceCategory;
use App\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfessionalResourceTest extends TestCase
{
    use RefreshDatabase;

    public function testPublicPageIsAvailableWithEmptyState()
    {
        $response = $this->get('/fagstoff');

        $response->assertStatus(200);
        $response->assertSee('Anbefalt fagstoff');
        $response->assertSee('Her finner du et utvalg fagressurser som kan være nyttige for fengselsbetjenter');
        $response->assertSee('Dette er ikke en offisiell side for kriminalomsorgen');
        $response->assertSee('Det publiseres fagressurser fortløpende');
        $response->assertSee('Siden er under oppbygging, og nye anbefalinger legges til etter hvert som de er vurdert og godkjent.');
        $response->assertDontSee('administrasjonspanelet');
    }

    public function testFrontPageLinksToProfessionalResources()
    {
        $response = $this->get('/tv');

        $response->assertStatus(200);
        $response->assertSee('Anbefalt fagstoff');
        $response->assertSee('Utvalgte ressurser for ansatte og andre interesserte');
        $response->assertSee('href="' . route('professional-resources.index') . '"', false);
        $response->assertDontSee('Kuratert');
        $response->assertDontSee('kuratert');
    }

    public function testOnlyPublishedResourcesInActiveCategoriesAreShownPublicly()
    {
        $activeCategory = $this->createCategory(['name' => 'Aktiv kategori']);
        $inactiveCategory = $this->createCategory(['name' => 'Inaktiv kategori', 'slug' => 'inaktiv', 'is_active' => false]);
        $emptyCategory = $this->createCategory(['name' => 'Tom kategori', 'slug' => 'tom']);

        $published = $this->createResource($activeCategory, [
            'title' => 'Publisert ressurs',
            'status' => ProfessionalResource::STATUS_PUBLISHED,
            'comment' => 'God ressurs.',
            'is_featured' => true,
            'publisher' => 'Utgiver',
            'content_type' => 'Rapport',
            'media_type' => 'report',
            'publication_year' => 2024,
        ]);
        $this->createResource($activeCategory, [
            'title' => 'Kladd ressurs',
            'status' => ProfessionalResource::STATUS_DRAFT,
        ]);
        $this->createResource($inactiveCategory, [
            'title' => 'Skjult ressurs',
            'status' => ProfessionalResource::STATUS_PUBLISHED,
            'comment' => 'Skal ikke vises.',
        ]);

        $response = $this->get('/fagstoff');

        $response->assertStatus(200);
        $response->assertSee($activeCategory->name);
        $response->assertSee($published->title);
        $response->assertSee('Særlig anbefalt');
        $response->assertSee('Utgiver');
        $response->assertSee('📘 Rapport');
        $response->assertSee('2024');
        $response->assertSee('target="_blank"', false);
        $response->assertSee('rel="noopener noreferrer"', false);
        $response->assertDontSee('Kladd ressurs');
        $response->assertDontSee('Skjult ressurs');
        $response->assertDontSee($inactiveCategory->name);
        $response->assertDontSee($emptyCategory->name);
    }

    public function testAdminRoutesRequireLogin()
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/admin/fagstoff')->assertRedirect(route('admin.login'));
        $this->get('/admin/fagstoff/kategorier')->assertRedirect(route('admin.login'));
    }

    public function testAdminDashboardIsAvailableForAuthenticatedAdmin()
    {
        $category = $this->createCategory(['is_active' => true]);
        $this->createResource($category, ['status' => ProfessionalResource::STATUS_PUBLISHED]);
        $this->createResource($category, ['status' => ProfessionalResource::STATUS_DRAFT]);

        $response = $this->adminSession()->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Anbefalt fagstoff');
        $response->assertSee('Publisert');
        $response->assertSee('Kladd');
        $response->assertSee('Aktive kategorier');
        $response->assertSee('Ressurser');
        $response->assertSee('Kategorier');
        $response->assertSee('Logg ut');
    }

    public function testAdminLinkOnPublicPageOnlyShowsForAuthenticatedAdmin()
    {
        $this->get('/fagstoff')
            ->assertDontSee('Gå til administrasjonspanelet');

        $this->adminSession()
            ->get('/fagstoff')
            ->assertSee('Gå til administrasjonspanelet');
    }

    public function testProfessionalResourcesPageDoesNotShowRedundantNavBox()
    {
        $response = $this->get('/fagstoff');

        $response->assertSee('Anbefalt fagstoff');
        $response->assertDontSee('site-nav-link', false);
    }

    public function testAdminCanLoginAndLogout()
    {
        config()->set('admin.email', 'admin@example.test');
        config()->set('admin.password_hash', Hash::make('hemmelig-passord'));

        $login = $this->post('/admin/login', [
            'email' => 'admin@example.test',
            'password' => 'hemmelig-passord',
        ]);

        $login->assertRedirect(route('admin.professional-resources.index'));
        $login->assertSessionHas('admin_authenticated', true);

        $logout = $this
            ->withSession(['admin_authenticated' => true])
            ->post('/admin/logout');

        $logout->assertRedirect(route('admin.login'));
        $logout->assertSessionMissing('admin_authenticated');
    }

    public function testAdminCanCreateAndEditCategoryAndResource()
    {
        $category = $this->createCategory();
        $newCategory = [
            'name' => 'Ny kategori',
            'description' => 'Kort beskrivelse.',
            'sort_order' => 20,
            'is_active' => '1',
        ];

        $this->adminSession()
            ->post(route('admin.resource-categories.store'), $newCategory)
            ->assertRedirect(route('admin.resource-categories.index'));

        $this->assertDatabaseHas('resource_categories', [
            'name' => 'Ny kategori',
            'slug' => 'ny-kategori',
            'is_active' => true,
        ]);

        $create = $this->adminSession()
            ->post(route('admin.professional-resources.store'), $this->validResourceData($category, [
                'title' => 'Ny fagressurs',
                'status' => ProfessionalResource::STATUS_DRAFT,
                'comment' => null,
            ]));

        $resource = ProfessionalResource::where('title', 'Ny fagressurs')->firstOrFail();
        $create->assertRedirect(route('admin.professional-resources.edit', $resource));

        $this->adminSession()
            ->patch(route('admin.professional-resources.update', $resource), $this->validResourceData($category, [
                'title' => 'Oppdatert fagressurs',
                'status' => ProfessionalResource::STATUS_PUBLISHED,
                'comment' => 'Administrator anbefaler denne.',
            ]))
            ->assertRedirect(route('admin.professional-resources.edit', $resource));

        $this->assertDatabaseHas('professional_resources', [
            'id' => $resource->id,
            'title' => 'Oppdatert fagressurs',
            'status' => ProfessionalResource::STATUS_PUBLISHED,
            'comment' => 'Administrator anbefaler denne.',
        ]);
    }

    public function testAdminCanPublishAndUnpublish()
    {
        $category = $this->createCategory();
        $resource = $this->createResource($category, [
            'status' => ProfessionalResource::STATUS_DRAFT,
            'comment' => 'Klar for publisering.',
            'media_type' => 'article',
        ]);

        $this->adminSession()
            ->patch(route('admin.professional-resources.publish', $resource))
            ->assertRedirect(route('admin.professional-resources.index'));

        $this->assertDatabaseHas('professional_resources', [
            'id' => $resource->id,
            'status' => ProfessionalResource::STATUS_PUBLISHED,
        ]);
        $this->assertNotNull($resource->fresh()->published_at);

        $this->adminSession()
            ->patch(route('admin.professional-resources.unpublish', $resource))
            ->assertRedirect(route('admin.professional-resources.index'));

        $this->assertDatabaseHas('professional_resources', [
            'id' => $resource->id,
            'status' => ProfessionalResource::STATUS_DRAFT,
            'published_at' => null,
        ]);
    }

    public function testValidationRequiresHttpsUrlAndRequiredFields()
    {
        $category = $this->createCategory();

        $response = $this->adminSession()
            ->from(route('admin.professional-resources.create'))
            ->post(route('admin.professional-resources.store'), $this->validResourceData($category, [
                'title' => '',
                'url' => 'http://example.test',
                'status' => ProfessionalResource::STATUS_PUBLISHED,
                'comment' => '',
            ]));

        $response->assertRedirect(route('admin.professional-resources.create'));
        $response->assertSessionHasErrors(['title', 'url', 'comment']);
    }

    public function testPublishedResourceRequiresValidMediaType()
    {
        $category = $this->createCategory();

        $missing = $this->adminSession()
            ->from(route('admin.professional-resources.create'))
            ->post(route('admin.professional-resources.store'), $this->validResourceData($category, [
                'status' => ProfessionalResource::STATUS_PUBLISHED,
                'media_type' => '',
            ]));

        $missing->assertRedirect(route('admin.professional-resources.create'));
        $missing->assertSessionHasErrors(['media_type']);

        $invalid = $this->adminSession()
            ->from(route('admin.professional-resources.create'))
            ->post(route('admin.professional-resources.store'), $this->validResourceData($category, [
                'media_type' => 'bok',
            ]));

        $invalid->assertRedirect(route('admin.professional-resources.create'));
        $invalid->assertSessionHasErrors(['media_type']);
    }

    public function testDraftCanBeSavedWithoutMediaType()
    {
        $category = $this->createCategory();

        $this->adminSession()
            ->post(route('admin.professional-resources.store'), $this->validResourceData($category, [
                'status' => ProfessionalResource::STATUS_DRAFT,
                'media_type' => '',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('professional_resources', [
            'title' => 'Gyldig fagressurs',
            'media_type' => null,
            'status' => ProfessionalResource::STATUS_DRAFT,
        ]);
    }

    public function testPublicFiltersCanUseCategoryMediaTypeAndTagTogether()
    {
        $category = $this->createCategory([
            'name' => 'Tilbakeføring og endringsarbeid',
            'slug' => 'tilbakeforing-og-endringsarbeid',
        ]);
        $otherCategory = $this->createCategory(['name' => 'Annen kategori', 'slug' => 'annen-kategori']);

        $podcast = $this->createResource($category, [
            'title' => 'Podkast om arbeid',
            'status' => ProfessionalResource::STATUS_PUBLISHED,
            'media_type' => 'podcast',
        ]);
        $podcast->tags()->attach(Tag::create(['name' => 'Arbeid', 'slug' => 'arbeid']));

        $this->createResource($category, [
            'title' => 'Rapport om arbeid',
            'status' => ProfessionalResource::STATUS_PUBLISHED,
            'media_type' => 'report',
        ]);
        $this->createResource($otherCategory, [
            'title' => 'Podkast i annen kategori',
            'status' => ProfessionalResource::STATUS_PUBLISHED,
            'media_type' => 'podcast',
        ]);

        $categoryResponse = $this->get('/fagstoff?category=tilbakeforing-og-endringsarbeid');
        $categoryResponse->assertSee('Podkast om arbeid');
        $categoryResponse->assertSee('Rapport om arbeid');
        $categoryResponse->assertDontSee('Podkast i annen kategori');

        $mediaResponse = $this->get('/fagstoff?media_type=podcast');
        $mediaResponse->assertSee('Podkast om arbeid');
        $mediaResponse->assertSee('Podkast i annen kategori');
        $mediaResponse->assertDontSee('Rapport om arbeid');

        $combinedResponse = $this->get('/fagstoff?category=tilbakeforing-og-endringsarbeid&media_type=podcast&tag=arbeid');
        $combinedResponse->assertSee('Podkast om arbeid');
        $combinedResponse->assertSee('🎧 Podkast');
        $combinedResponse->assertSee('Arbeid');
        $combinedResponse->assertDontSee('Rapport om arbeid');
        $combinedResponse->assertDontSee('Podkast i annen kategori');
        $combinedResponse->assertSee('value="tilbakeforing-og-endringsarbeid" selected', false);
        $combinedResponse->assertSee('value="podcast" selected', false);
        $combinedResponse->assertSee('value="arbeid" selected', false);
    }

    public function testNoFilterMatchesShowsSeparateEmptyState()
    {
        $category = $this->createCategory();
        $this->createResource($category, [
            'title' => 'Publisert artikkel',
            'status' => ProfessionalResource::STATUS_PUBLISHED,
            'media_type' => 'article',
        ]);

        $response = $this->get('/fagstoff?media_type=video');

        $response->assertSee('Ingen fagressurser passer filtrene');
        $response->assertSee('Nullstill filter');
        $response->assertDontSee('Det publiseres fagressurser fortløpende');
    }

    public function testTagsAreCreatedReusedUpdatedAndDetached()
    {
        $category = $this->createCategory();

        $this->adminSession()
            ->post(route('admin.professional-resources.store'), $this->validResourceData($category, [
                'title' => 'Ressurs med emneord',
                'tags' => 'desistance, tilbakeføring, arbeid, bolig, NAV, nav,  ',
            ]))
            ->assertRedirect();

        $resource = ProfessionalResource::where('title', 'Ressurs med emneord')->firstOrFail();

        $this->assertSame(5, Tag::count());
        $this->assertTrue($resource->tags()->where('slug', 'nav')->exists());
        $this->assertSame(5, $resource->tags()->count());

        $this->adminSession()
            ->patch(route('admin.professional-resources.update', $resource), $this->validResourceData($category, [
                'title' => 'Ressurs med oppdaterte emneord',
                'tags' => 'Arbeid, Familie',
            ]))
            ->assertRedirect(route('admin.professional-resources.edit', $resource));

        $resource->refresh();
        $this->assertSame(['arbeid', 'familie'], $resource->tags()->orderBy('slug')->pluck('slug')->all());
        $this->assertSame(6, Tag::count());
    }

    public function testTagOutputIsEscaped()
    {
        $category = $this->createCategory();
        $resource = $this->createResource($category, [
            'status' => ProfessionalResource::STATUS_PUBLISHED,
            'media_type' => 'article',
        ]);
        $resource->tags()->attach(Tag::create([
            'name' => '<script>alert("tag")</script>',
            'slug' => 'script-alert-tag-script',
        ]));

        $response = $this->get('/fagstoff');

        $response->assertSee('&lt;script&gt;alert(&quot;tag&quot;)&lt;/script&gt;', false);
        $response->assertDontSee('<script>alert("tag")</script>', false);
    }

    public function testCategoryWithResourcesCannotBeDeleted()
    {
        $category = $this->createCategory();
        $this->createResource($category);

        $this->adminSession()
            ->delete(route('admin.resource-categories.destroy', $category))
            ->assertRedirect(route('admin.resource-categories.index'))
            ->assertSessionHas('warning', 'Kategorien kan ikke slettes fordi den inneholder fagressurser.');

        $this->assertDatabaseHas('resource_categories', ['id' => $category->id]);
    }

    public function testUserContentIsEscaped()
    {
        $category = $this->createCategory();
        $this->createResource($category, [
            'title' => '<script>alert("tittel")</script>',
            'comment' => '<script>alert("kommentar")</script>',
            'status' => ProfessionalResource::STATUS_PUBLISHED,
        ]);

        $response = $this->get('/fagstoff');

        $response->assertSee('&lt;script&gt;alert(&quot;tittel&quot;)&lt;/script&gt;', false);
        $response->assertSee('&lt;script&gt;alert(&quot;kommentar&quot;)&lt;/script&gt;', false);
        $response->assertDontSee('<script>alert("tittel")</script>', false);
    }

    public function testResourceCategorySeederCanRunMultipleTimesWithoutDuplicates()
    {
        $this->seed('ResourceCategorySeeder');
        $this->seed('ResourceCategorySeeder');

        $this->assertSame(10, ResourceCategory::count());
        $this->assertSame(10, ResourceCategory::distinct('slug')->count('slug'));
        $this->assertSame(0, ProfessionalResource::count());
        $this->assertSame(1, ResourceCategory::where('name', 'Barn, unge, familier og pårørende')->count());
        $this->assertSame(0, ResourceCategory::where('name', 'Barn, unge og familier')->count());
    }

    public function testCategoryRenameMigrationPreservesExistingRowAndResources()
    {
        $category = $this->createCategory([
            'name' => 'Barn, unge og familier',
            'slug' => 'barn-unge-og-familier',
        ]);
        $resource = $this->createResource($category);

        require_once database_path('migrations/2026_07_12_014000_rename_barn_unge_familier_category.php');
        (new \RenameBarnUngeFamilierCategory())->up();

        $category->refresh();
        $resource->refresh();

        $this->assertSame('Barn, unge, familier og pårørende', $category->name);
        $this->assertSame('barn-unge-familier-og-parorende', $category->slug);
        $this->assertSame($category->id, $resource->category_id);
        $this->assertSame(1, ResourceCategory::where('name', 'Barn, unge, familier og pårørende')->count());
        $this->assertSame(0, ResourceCategory::where('name', 'Barn, unge og familier')->count());
    }

    private function createCategory(array $attributes = []): ResourceCategory
    {
        return ResourceCategory::create(array_merge([
            'name' => 'Fagkategori',
            'slug' => 'fagkategori',
            'description' => null,
            'sort_order' => 10,
            'is_active' => true,
        ], $attributes));
    }

    private function createResource(ResourceCategory $category, array $attributes = []): ProfessionalResource
    {
        return ProfessionalResource::create(array_merge([
            'category_id' => $category->id,
            'title' => 'Fagressurs',
            'url' => 'https://example.test/fagressurs',
            'comment' => 'Kort anbefaling.',
            'publisher' => null,
            'content_type' => null,
            'media_type' => null,
            'publication_year' => null,
            'is_featured' => false,
            'status' => ProfessionalResource::STATUS_DRAFT,
            'sort_order' => 10,
            'last_checked_at' => null,
            'published_at' => null,
        ], $attributes));
    }

    private function validResourceData(ResourceCategory $category, array $attributes = []): array
    {
        return array_merge([
            'category_id' => $category->id,
            'title' => 'Gyldig fagressurs',
            'url' => 'https://example.test/gyldig',
            'comment' => 'Kort anbefaling.',
            'publisher' => 'Utgiver',
            'content_type' => 'Veileder',
            'media_type' => 'guide',
            'tags' => '',
            'publication_year' => 2024,
            'is_featured' => '1',
            'status' => ProfessionalResource::STATUS_DRAFT,
            'sort_order' => 10,
            'last_checked_at' => '2026-07-12',
        ], $attributes);
    }

    private function adminSession()
    {
        return $this->withSession(['admin_authenticated' => true]);
    }
}
