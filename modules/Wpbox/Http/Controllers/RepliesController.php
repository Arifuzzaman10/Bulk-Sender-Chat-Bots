<?php

namespace Modules\Wpbox\Http\Controllers;

use Modules\Wpbox\Models\Reply;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RepliesController extends Controller
{
    /**
     * Provide class.
     */
    private $provider = Reply::class;

    /**
     * Web RoutePath for the name of the routes.
     */
    private $webroute_path = 'replies.';

    /**
     * View path.
     */
    private $view_path = 'wpbox::replies.';

    /**
     * Parameter name.
     */
    private $parameter_name = 'reply';

    /**
     * Title of this crud.
     */
    private $title = 'reply';

    /**
     * Title of this crud in plural.
     */
    private $titlePlural = 'replies';

    private function getFields($class='col-md-4')
    {
        $fields=[];
        
        //Add name field
        $fields[0]=['class'=>$class, 'ftype'=>'input', 'name'=>'Name', 'id'=>'name', 'placeholder'=>'Enter name', 'required'=>true];

        //Add text field
        $fields[1]=['class'=>$class, 'ftype'=>'textarea', 'name'=>'Reply text', 'id'=>'text', 'placeholder'=>'Enter reply text', 'required'=>true,'additionalInfo'=>__('Text that will be send to contact. You can also use {{name}},{{phone}} on any other custom field name')];

        //Type
        $fields[2]=['class'=>$class, 'value'=>"1", 'ftype'=>'select', 'name'=>'Reply type', 'id'=>'type', 'placeholder'=>'Select reply type', 'data'=>['1'=>__('Just a quick reply'),'2'=>__('Reply bot: On exact match'),'3'=>__('Reply bot: When message contains')], 'required'=>true];
        
        //Add trigger field
        $fields[3]=['class'=>$class, 'ftype'=>'input', 'name'=>'Trigger', 'id'=>'trigger', 'placeholder'=>'Enter bot reply trigger', 'required'=>true];

        //Return fields
        return $fields;
    }


    private function getFilterFields(){
        $fields=$this->getFields('col-md-3');
        $fields[0]['required']=true;
        unset($fields[1]);
        unset($fields[2]);
        unset($fields[3]);
        return $fields;
    }

    /**
     * Auth checker functin for the crud.
     */
    private function authChecker()
    {
        $this->ownerAndStaffOnly();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authChecker();

        $items=$this->provider::orderBy('id', 'desc');
        if(isset($_GET['name'])&&strlen($_GET['name'])>1){
            $items=$items->where('name',  'like', '%'.$_GET['name'].'%');
        }
        $items=$items->paginate(config('settings.paginate'));

        return view($this->view_path.'index', ['setup' => [
            'usefilter'=>true,
            'title'=>__('Bot and Quick Replies management'),
            'action_link'=>route($this->webroute_path.'create'),
            'action_name'=>__('crud.add_new_item', ['item'=>__($this->title)]),
            'items'=>$items,
            'item_names'=>$this->titlePlural,
            'webroute_path'=>$this->webroute_path,
            'fields'=>$this->getFields(),
            'filterFields'=>$this->getFilterFields(),
            'custom_table'=>true,
            'parameter_name'=>$this->parameter_name,
            'parameters'=>count($_GET) != 0
        ]]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authChecker();


        return view('general.form', ['setup' => [
            'title'=>__('crud.new_item', ['item'=>__($this->title)]),
            'action_link'=>route($this->webroute_path.'index'),
            'action_name'=>__('crud.back'),
            'iscontent'=>true,
            'action'=>route($this->webroute_path.'store')
        ],
        'fields'=>$this->getFields() ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authChecker();
        
        //Create new field
        $field = $this->provider::create([
            'name' => $request->name,
            'type' => $request->type,
            'text' => $request->text,
            'trigger' => $request->trigger,
        ]);
        $field->save();

        return redirect()->route($this->webroute_path.'index')->withStatus(__('crud.item_has_been_added', ['item'=>__($this->title)]));
    }

    

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Contact  $contacts
     * @return \Illuminate\Http\Response
     */
    public function edit(Reply $reply)
    {
        $this->authChecker();

        $fields = $this->getFields('col-md-6');
        $fields[0]['value'] = $reply->name;
        $fields[1]['value'] = $reply->text;
        $fields[2]['value'] = $reply->type;
        $fields[3]['value'] = $reply->trigger;

        $parameter = [];
        $parameter[$this->parameter_name] = $reply->id;

        return view($this->view_path.'edit', ['setup' => [
            'title'=>__('crud.edit_item_name', ['item'=>__($this->title), 'name'=>$reply->name]),
            'action_link'=>route($this->webroute_path.'index'),
            'action_name'=>__('crud.back'),
            'iscontent'=>true,
            'isupdate'=>true,
            'action'=>route($this->webroute_path.'update', $parameter),
        ],
        'fields'=>$fields, ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Contact  $contacts
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->authChecker();
        $item = $this->provider::findOrFail($id);
        $item->name = $request->name;
        $item->type = $request->type;
        $item->text = $request->text;
        $item->trigger = $request->trigger;
        $item->update();

        return redirect()->route($this->webroute_path.'index')->withStatus(__('crud.item_has_been_updated', ['item'=>__($this->title)]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Contact  $contacts
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->authChecker();
        $item = $this->provider::findOrFail($id);
        $item->delete();
        return redirect()->route($this->webroute_path.'index')->withStatus(__('crud.item_has_been_removed', ['item'=>__($this->title)]));
    }
    
}
