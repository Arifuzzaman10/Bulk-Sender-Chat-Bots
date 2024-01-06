<?php

namespace App\Scopes;

use App\Models\Company;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SetCompanyIdInSession
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $vendor=Company::where('user_id', $event->user->id)->first();
        if($vendor){
            session(['company_id' => $vendor->id]);
            session(['company_currency' => $vendor->currency]);
            session(['company_convertion' => $vendor->do_covertion]);
        }
        
    }
}
