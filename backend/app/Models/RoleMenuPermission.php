<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Model;

class RoleMenuPermission extends Model
{
    protected $fillable = [
        'role',
        'menu_id',
        'can_view',
        'can_create',
        'can_update',
        'can_delete',
    ];

    protected $casts = [
        'role' => Role::class,
        'can_view' => 'boolean',
        'can_create' => 'boolean',
        'can_update' => 'boolean',
        'can_delete' => 'boolean',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}