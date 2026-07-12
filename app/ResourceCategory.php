<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ResourceCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function professionalResources()
    {
        return $this->hasMany(ProfessionalResource::class, 'category_id');
    }

    public function publishedResources()
    {
        return $this->professionalResources()
            ->where('status', ProfessionalResource::STATUS_PUBLISHED)
            ->orderBy('sort_order')
            ->orderBy('title');
    }
}
