<?php

namespace Modules\Wpbox\Http\Controllers;


use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Contacts\Models\Group;
use Modules\Contacts\Models\Contact;
use Modules\Contacts\Models\Field;
use Modules\Wpbox\Models\Campaign;
use Modules\Wpbox\Models\Message;
use Modules\Wpbox\Models\Template;
use Modules\Wpbox\Traits\Whatsapp;

class CampaignsController extends Controller
{
    use Whatsapp;

    /**
     * Provide class.
     */
    private $provider = Campaign::class;

    /**
     * Web RoutePath for the name of the routes.
     */
    private $webroute_path = 'campaigns.';

    /**
     * View path.
     */
    private $view_path = 'wpbox::campaigns.';

    /**
     * Parameter name.
     */
    private $parameter_name = 'campaigns';

    /**
     * Title of this crud.
     */
    private $title = 'campaign';

    /**
     * Title of this crud in plural.
     */
    private $titlePlural = 'campaigns';


    public function index()
    {

        $this->authChecker();

        if($this->getCompany()->getConfig('whatsapp_webhook_verified','no')!='yes' || $this->getCompany()->getConfig('whatsapp_settings_done','no')!='yes'){
            return redirect(route('whatsapp.setup'));
         }

        $items=$this->provider::orderBy('id', 'desc')->whereNull('contact_id');
        if(isset($_GET['name'])&&strlen($_GET['name'])>1){
            $items=$items->where('name',  'like', '%'.$_GET['name'].'%');
        }
        $items=$items->paginate(config('settings.paginate'));
        

        return view($this->view_path.'index', [ 'total_contacts'=>Contact::count(),
        'setup' => [
           
            'title'=>__('crud.item_managment', ['item'=>__($this->titlePlural)]),
            'iscontent'=>true,
            'action_link'=>route($this->webroute_path.'create'),
            'action_name'=>__('Send new campaign')." 📢",
            'items'=>$items,
            'item_names'=>$this->titlePlural,
            'webroute_path'=>$this->webroute_path,
            'fields'=>[],
            'custom_table'=>true,
            'parameter_name'=>$this->parameter_name,
            'parameters'=>count($_GET) != 0
        ]]);
    }

    public function show(Campaign $campaign){

        //Get countries we have send to
        $contact_ids=$campaign->messages()->select(['contact_id'])->pluck('contact_id')->toArray();
        $countriesCount = DB::table('contacts')
        ->join('countries', 'contacts.country_id', '=', 'countries.id')
        ->selectRaw('count(contacts.id) as number_of_messages, country_id, countries.name, countries.lat, countries.lng')
        ->whereIn('contacts.id',$contact_ids)
        ->groupBy('contacts.country_id')
        ->get()->toArray();
 
        
        return view($this->view_path.'show', [ 
            'total_contacts'=>Contact::count(),
            'item'=>$campaign,
        'setup' => [
            'countriesCount'=>$countriesCount,
            'title'=>__('Campaign')." ".$campaign->name,
            'action_link'=>route($this->webroute_path.'index'),
            'action_name'=>__('Back to campaings')." 📢",
            'items'=>$campaign->messages()->paginate(config('settings.paginate')),
            'item_names'=>$this->titlePlural,
            'webroute_path'=>$this->webroute_path,
            'fields'=>[],
            'custom_table'=>true,
            'parameter_name'=>$this->parameter_name,
            'parameters'=>count($_GET) != 0
        ]]);
    }

    /**
     * Auth checker function for the crud.
     */
    private function authChecker()
    {
        $this->ownerAndStaffOnly();
    }

    private function componentToVariablesList($template){
        $jsonData = json_decode($template->components, true);

        $variables = [];
        foreach ($jsonData as $item) {

            if($item['type']=="HEADER"&&$item['format']=="TEXT"){
                preg_match_all('/{{(\d+)}}/', $item['text'], $matches);  
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $id) {
                        $exampleValue ="";
                        try {
                            $exampleValue = $item['example']['header_text'][$id - 1];
                        } catch (\Throwable $th) {
                        }
                        $variables['header'][] = ['id' => $id, 'exampleValue' => $exampleValue];
                    }
                }
            }else if($item['type']=="HEADER"&&$item['format']=="DOCUMENT"){
                $variables['document']=true;
            }else if($item['type']=="HEADER"&&$item['format']=="IMAGE"){
                $variables['image']=true;
            }else if($item['type']=="HEADER"&&$item['format']=="VIDEO"){
                $variables['video']=true;
            }else if($item['type']=="BODY"){
                preg_match_all('/{{(\d+)}}/', $item['text'], $matches);  
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $id) {
                        $exampleValue ="";
                        try {
                            $exampleValue = $item['example']['body_text'][0][$id - 1];
                        } catch (\Throwable $th) {
                        }
                        $variables['body'][] = ['id' => $id, 'exampleValue' => $exampleValue];
                    }
                }
            }else if($item['type']=="BUTTONS"){
                foreach ($item['buttons'] as $keyBtn => $button) {
                    if($button['type']=="URL"){
                        preg_match_all('/{{(\d+)}}/', $button['url'], $matches);  
                   
                        if (!empty($matches[1])) {
                        
                            foreach ($matches[1] as $id) {
                                $exampleValue ="";
                                try {
                                    $exampleValue = $button['url'];
                                    $exampleValue = str_replace("{{1}}", "", $exampleValue );
                                } catch (\Throwable $th) {
                                }
                                $variables['buttons'][$id - 1][] = ['id' => $id, 'exampleValue' => $exampleValue,'type'=>$button['type'],'text'=>$button['text']];
                            }
                        }
                    }
                    if($button['type']=="COPY_CODE"){
                        $exampleValue = $button['example'][0];
                        $variables['buttons'][$keyBtn][] = ['id' => $keyBtn, 'exampleValue' => $exampleValue,'type'=>$button['type'],'text'=>$button['text']];
                    }
                    
                }
               
            }
        }
        return $variables;
    }

    public function create(){
        $templates=[];
        foreach (Template::where('status','APPROVED')->get() as $key => $template) {
            $templates[$template->id]=$template->name." - ".$template->language;
        }
        if(sizeof($templates)==0){
           //If there are 0 template,re-load them
            try {
                $this->loadTemplatesFromWhatsApp();
                foreach (Template::where('status','APPROVED')->get() as $key => $template) {
                    $templates[$template->id]=$template->name." - ".$template->language;
                }
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
        
         

        if(sizeof($templates)==0){
            //Redirect to templates
            return redirect()->route('templates.index')->withStatus(__('Please add a template first. Or wait some to be approved'));
        }
    
        $groups=Group::pluck('name','id');
        $groups[0]=__("Send to all contacts");

        $selectedTemplate=null;
        $variables=null;
        if(isset($_GET['template_id'])){
            $selectedTemplate=Template::where('id',$_GET['template_id'])->first();
            $variables=$this->componentToVariablesList($selectedTemplate);
            
        }
        
        $contactFields=[];
        $contactFields[-2]=__('Use manually defined value');
        $contactFields[-1]=__('Contact name');
        $contactFields[0]=__('Contact phone');
        foreach (Field::pluck('name','id') as $key => $value) {
            $contactFields[$key]=$value;
        }
       
        return view($this->view_path.'create', [
            'selectedContacts'=>isset($_GET['group_id'])? ($_GET['group_id'].""=="0"?Contact::count():Group::findOrFail($_GET['group_id'])->contacts->count()):"",
            'selectedTemplate'=>$selectedTemplate,
            'selectedTemplateComponents'=>$selectedTemplate?json_decode($selectedTemplate->components,true):null,  
            'contactFields'=> $contactFields,
            'variables'=>$variables,
            'groups' => $groups,
            'contacts' => Contact::pluck('name','id'),
            'templates' => $templates
        ]);
    }


    public function store(Request $request) {  
        //Create the campaign
        $campaign = $this->provider::create([
            'name'=>$request->has('name') ? $request->name:"template_message_".now(),
            'timestamp_for_delivery'=>$request->has('send_now')?null:$request->send_time,
            'variables'=>$request->has('paramvalues')?json_encode($request->paramvalues):"",
            'variables_match'=>json_encode($request->parammatch),
            'template_id'=>$request->template_id,
            'group_id'=>$request->group_id.""=="0"?null:$request->group_id,
            'contact_id'=>$request->contact_id,
            'total_contacts'=>Contact::count(),
        ]);

        if ($request->hasFile('pdf')) {
            $campaign->media_link = $this->saveDocument(
                "",
                $request->pdf,
            );
            $campaign->update();
        }
        if ($request->hasFile('imageupload')) {
            $campaign->media_link = $this->saveDocument(
                "",
                $request->imageupload,
            );
            $campaign->update();
        }

    

        //Make the actual messages
        $this->makeMessages($campaign,$request);

        if($request->has('contact_id')){
            return redirect()->route('chat.index')->withStatus(__('Message will be send shortly. Please note that if new contact, it will not appear in this list until the contact start interacting with you!'));
        }else{
            return redirect()->route($this->webroute_path.'index')->withStatus(__('Campaign is ready to be send'));
        }

       
    
    }

    private function makeMessages(Campaign $campaign,Request $request){
        //For each contact, send the message

        //1. Find all the contact that this message should be send to
        if($campaign->group_id==null&&$campaign->contact_id==null){
            //All contacts
            $contacts=Contact::get();
        }else if($campaign->group_id!=null){
            //Specific group
            $contacts=Group::findOrFail($campaign->group_id)->contacts()->get();
        }else if($campaign->contact_id!=null){
            //Specific contact
            $contacts=Contact::where('id',$campaign->contact_id)->get();
        }
       
        //Prepate what we need
        $template=Template::where('id',$campaign->template_id)->first();
        $variablesValues=json_decode($campaign->variables,true);
        $variables_match=json_decode($campaign->variables_match,true);
        $messages=[];

        $campaign->send_to=$contacts->count();
        $campaign->update();
       

        //For each contact prepare the message

        // Parse the date string into a Carbon instance
        $tzBasedDelivery=false;
        $companyRelatedDateTimeOfSend=null;
        if(!$request->has('send_now')&&$request->has('send_time')&&$request->send_time!=null){
            $company=$this->getCompany();

           //Set config based on restaurant
           config(['app.timezone' => $company->getConfig('time_zone',config('app.timezone'))]);


            $companyRelatedDateTimeOfSend = Carbon::parse($request->send_time); //This will be set time in company time
            //Convert to system time
            $systemRelatedDateTimeOfSend = $companyRelatedDateTimeOfSend->copy()->tz(config('app.timezone'));//System time, can be the same
            $tzBasedDelivery=true;
        }
        
        foreach ($contacts as $key => $contact) {

            $content="";
            $header_text="";
            $header_image="";
            $header_document="";
            $header_video="";
            $header_audio="";
            $footer="";
            $buttons=[];
            
            $sendTime=Carbon::now();//Send now
            if($tzBasedDelivery){
                    try {
                        //Calculate time based on the client time zone
                        $sendTime=Carbon::parse($systemRelatedDateTimeOfSend->format('Y-m-d H:i:s'),$contact->country->timezone)->copy()->tz(config('app.timezone'))->format('Y-m-d H:i:s');
                    } catch (\Throwable $th) {
                       
                    }
            }

            //Make the components
            $components=json_decode($template->components,true); 
            $APIComponents=[];
            foreach ($components as $keyComponent => $component) {
                $lowKey=strtolower($component['type']);

                if($component['type']=="HEADER"&&$component['format']=="TEXT"){
                    $header_text=$component['text'];
                    $component['parameters']=[];
                   
                    if(isset($variables_match[$lowKey])){
                        $this->setParameter($variables_match[$lowKey],$variablesValues[$lowKey],$component,$header_text,$contact);
                        unset($component['text']);
                        unset($component['format']);
                        unset($component['example']);
                        array_push($APIComponents,$component);
                    }
                    
                }else if($component['type']=="BODY"){
                    $content=$component['text'];
                    $component['parameters']=[];
                    if(isset($variables_match[$lowKey])){
                        $this->setParameter($variables_match[$lowKey],$variablesValues[$lowKey],$component,$content,$contact);
                        unset($component['text']);
                        unset($component['format']);
                        unset($component['example']);
                        array_push($APIComponents,$component);
                    }
                    
                }else if(($component['type']=="HEADER"&&$component['format']=="DOCUMENT")){
                    $component['parameters']=[[
                        "type"=> "document",
                        "document"=>[
                            'link'=>$campaign->media_link
                        ]
                    ]];
                    $header_document=$campaign->media_link;
                    unset($component['format']);
                    unset($component['example']);
                    array_push($APIComponents,$component);
                }else if(($component['type']=="HEADER"&&$component['format']=="IMAGE")){
                    $component['parameters']=[[
                        "type"=> "image",
                        "image"=>[
                            'link'=>$campaign->media_link
                        ]
                    ]];
                    $header_image=$campaign->media_link;
                    unset($component['format']);
                    unset($component['example']);
                    array_push($APIComponents,$component);
                }else if(($component['type']=="HEADER"&&$component['format']=="VIDEO")){
                    $component['parameters']=[[
                        "type"=> "video",
                        "video"=>[
                            'link'=>$campaign->media_link
                        ]
                    ]];
                    $header_video=$campaign->media_link;
                    unset($component['format']);
                    unset($component['example']);
                    array_push($APIComponents,$component);
                }else if(($component['type']=="HEADER"&&$component['format']=="AUDIO")){
                    $component['parameters']=[[
                        "type"=> "audio",
                        "audio"=>[
                            'link'=>$campaign->media_link
                        ]
                    ]];
                    $header_audio=$campaign->media_link;
                    unset($component['format']);
                    unset($component['example']);
                    array_push($APIComponents,$component);
                }else if($component['type']=="FOOTER"){
                    $footer=$component['text'];
                }else if( $component['type']=="BUTTONS"){
                    $keyButton=0;
                    foreach ($component['buttons'] as $keyButtonFromLoop => $valueButton) {
                    
                         if(isset($variables_match[$lowKey][$keyButton]) && (($valueButton['type']=="URL"&&stripos($valueButton['url'], "{{") !== false) || ($valueButton['type']=="COPY_CODE")) ){
                            $buttonName="";
                            $button=[
                                "type"=>"button",
                                "sub_type"=>strtolower($valueButton['type']),
                                "index"=>$keyButtonFromLoop."",
                                "parameters"=>[]
                            ]; 
                            $paramType="text";
                            if($valueButton['type']=="COPY_CODE"){
                                $paramType="coupon_code";
                            }
                           
                            $this->setParameter($variables_match[$lowKey][$keyButton],$variablesValues[$lowKey][$keyButton],$button,$buttonName,$contact,$paramType);
                
                            
                            array_push($APIComponents,$button);
                            array_push($buttons,$valueButton);
                            $keyButton++;
                         }else{
                            array_push($buttons,$valueButton);
                         }
                         
                    }
                    
                }

                
            }
            $components=$APIComponents;

            $dataToSend=[
                "contact_id"=>$contact->id,
                "company_id"=>$contact->company_id,
                "value"=>$content,
                "header_image"=>$header_image,
                "header_video"=>$header_video,
                "header_audio"=>$header_audio,
                "header_document"=>$header_document,
                "footer_text"=>$footer,
                "buttons"=>json_encode($buttons),
                "header_text"=>$header_text,
                "is_message_by_contact"=>false,
                "is_campign_messages"=>true,
                "status"=>0,
                "created_at"=>now(),
                "scchuduled_at"=>$sendTime,
                "components"=>json_encode($components),
                "campaign_id"=>$campaign->id,
            ];

            if(config('settings.is_demo',false)){
                //Demo
                if(count($messages)<5){
                    //Allow, but let it know
                    $dataToSend['value']="[THIS IS DEMO] ".$dataToSend['value'];
                    array_push($messages,$dataToSend);
                }
                
            }else{
                //Production
                array_push($messages,$dataToSend);
            }

            
        }
        Message::insert($messages);
    }

    private function setParameter($variables,$values,&$component,&$content,$contact,$type="text"){
        foreach ($variables as $keyVM => $vm) { 
            $data=["type"=>$type];
            if($vm=="-2"){
                //Use static value
                $data[$type]=$values[$keyVM];
                array_push($component['parameters'],$data);
                $content=str_replace("{{".$keyVM."}}",$values[$keyVM],$content);
                
            }else if($vm=="-1"){
                //Contact name
                $data[$type]=$contact->name;
                array_push($component['parameters'],$data);
                $content=str_replace("{{".$keyVM."}}",$contact->name,$content);
            }else if($vm=="0"){
                //Contact phone
                $data[$type]=$contact->phone;
                array_push($component['parameters'],$data);
                $content=str_replace("{{".$keyVM."}}",$contact->phone,$content);
            }else{
                //Use defined contact field
                if($contact->fields->where('id',$vm)->first()){
                    $val=$contact->fields->where('id',$vm)->first()->pivot->value;
                    $data[$type]=$val;
                    array_push($component['parameters'],$data);
                    $content=str_replace("{{".$keyVM."}}",$val,$content);
                }else{
                    $data[$type]="";
                    array_push($component['parameters'],$data);
                    $content=str_replace("{{".$keyVM."}}","",$content);
                }
            }
        }
    }

    public function sendSchuduledMessages(){
        //Find all unsent Messages that are within the timeline
        $messagesToBeSend=Message::where('status',0)->where('scchuduled_at', '<',Carbon::now())->limit(100)->get();
        foreach ( $messagesToBeSend as $key => $message) {
            $this->sendCampaignMessageToWhatsApp($message);
        }

    }
}