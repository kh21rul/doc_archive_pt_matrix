<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    /** @use HasFactory<\Database\Factories\DivisionFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    // Relationship dengan users (many-to-many)
    public function users()
    {
        return $this->belongsToMany(User::class, 'division_user')
            ->withTimestamps();
    }
}
