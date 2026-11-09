<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DynamicTable extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    public function columns()
    {
        return $this->hasMany(DynamicColumn::class)->orderBy('order');
    }

    public function records()
    {
        return $this->hasMany(DynamicRecord::class);
    }
}
