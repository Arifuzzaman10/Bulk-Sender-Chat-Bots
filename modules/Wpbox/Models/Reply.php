<?php

namespace Modules\Wpbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\CompanyScope;
use Modules\Contacts\Models\Contact;
use PDO;

use function Ramsey\Uuid\v1;

class Reply extends Model
{
   // use SoftDeletes;
    
    protected $table = 'replies';
    public $guarded = [];

    public function shouldWeUseIt($receivedMessage,Contact $contact){
        $receivedMessage = " " . strtolower($receivedMessage);
        $message = "";

        // Store the value of $this->trigger in a new variable
        $triggerValues = $this->trigger;

        // Convert $triggerValues into an array if it contains commas
        if (strpos($triggerValues, ',') !== false) {
            $triggerValues = explode(',', $triggerValues);
        }

        if (is_array($triggerValues)) {
            foreach ($triggerValues as $trigger) {
                if ($this->type == 2) {
                    // Exact match
                    if ($receivedMessage == " " . $trigger) {
                        $message = $this->text;
                        break; // exit the loop once a match is found
                    }
                } else if ($this->type == 3) {
                    // Contains
                    if (stripos($receivedMessage, $trigger) !== false) {
                        $message = $this->text;
                        break; // exit the loop once a match is found
                    }
                }
            }
        } else {
            //Doesn't contain commas
            if ($this->type == 2) {
                // Exact match
                if ($receivedMessage == " " . $triggerValues) {
                    $message = $this->text;
                }
            } else if ($this->type == 3) {
                // Contains
                if (stripos($receivedMessage, $triggerValues) !== false) {
                    $message = $this->text;
                }
            }
        }
        
        //Change message
        if($message!=""){
            $this->increment('used', 1);
            $this->update();


            $pattern = '/{{\s*([^}]+)\s*}}/';
            preg_match_all($pattern, $message, $matches);
            $variables = $matches[1];
            foreach ($variables as $key => $variable) {
                if($variable=="name"){
                    $message=str_replace("{{".$variable."}}",$contact->name,$message);
                }else if($variable=="phone"){
                    $message=str_replace("{{".$variable."}}",$contact->phone,$message);
                }else{
                    //Field
                    $val=$contact->fields->where('name',$variable)->first()->pivot->value;
                    $message=str_replace("{{".$variable."}}",$val,$message);
                }
            }
            
            $contact->sendMessage($message,false);
           
        }

        
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
