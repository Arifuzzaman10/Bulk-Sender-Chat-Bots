<?php

namespace Modules\Wpbox\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\CompanyScope;

class Campaign extends Model
{
   // use SoftDeletes;
    
    protected $table = 'wa_campaings';
    public $guarded = [];

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
    protected static function booted(){
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model){
           $company_id=session('company_id',null);
            if($company_id){
                $model->company_id=$company_id;
            }
        });
    }
}
