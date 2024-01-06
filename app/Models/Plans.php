<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasConfig;

class Plans extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasConfig;

    protected $modelName="App\Models\Plan";
    protected $table = 'plan';
    protected $guarded=[];
}
