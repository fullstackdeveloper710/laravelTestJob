<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Token extends Model
{
    protected $table = 'temp_url_token';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'token',      
    ];


}
