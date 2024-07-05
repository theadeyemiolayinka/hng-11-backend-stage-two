<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organisation extends Model
{
    use HasFactory;

    protected $primaryKey = 'orgId';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'orgId', 'name', 'description'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'organisation_user', 'orgId', 'userId');
    }
}
