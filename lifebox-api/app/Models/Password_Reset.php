<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Password_Reset
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Password_Reset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Password_Reset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Password_Reset query()
 * @mixin \Eloquent
 */
class Password_Reset extends Model
{
    use HasFactory;
    protected $fillable = [
        'email',
        'token', 
    ];
}
