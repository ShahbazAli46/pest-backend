<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;
    public $table = "permissions";

    protected $fillable = ['name', 'icon', 'api_route', 'frontend_url', 'parent_api_route', 'is_main'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_has_permissions');
    }

    public function parent()
    {
        return $this->belongsTo(Permission::class, 'parent_api_route', 'api_route');
    }

    public function children()
    {
        return $this->hasMany(Permission::class, 'parent_api_route', 'api_route');
    }

}
