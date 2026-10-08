<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'is_active',
        'is_auditee',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_auditee' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}