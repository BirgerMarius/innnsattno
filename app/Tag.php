<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    public function professionalResources()
    {
        return $this->belongsToMany(ProfessionalResource::class, 'professional_resource_tag');
    }
}
