<div class="campign_elements">
@if (!isset($_GET['contact_id']))
    @include('partials.input',['id'=>'name','name'=>'Campaign name','placeholder'=>'Name for your campaing', 'required'=>false])
@endif
@include('partials.select',['id'=>'template_id','name'=>'Template','data'=>$templates, 'required'=>true])

@if (isset($_GET['contact_id']))
    @include('partials.select',['id'=>'contact_id','name'=>'Contact','data'=>$contacts, 'required'=>true])
@else
    @include('partials.select',['id'=>'group_id','name'=>'Contacts','data'=>$groups, 'required'=>false])
<div class="form-group">
    <label for="example-datetime-local-input" class="form-control-label">{{ __('Schuduled send time') }}</label>
    <input class="form-control" type="datetime-local" @isset($_GET['send_time'])
        value="{{$_GET['send_time']}}"
    @endisset id="send_time" name="send_time" min="{{ \Carbon\Carbon::now()->format('Y-m-d\TH:i')}}">
    <small class="text-muted"><strong>{{ __('Per client, based on the contact timezone') }}</strong></small>
</div>
@include('partials.toggle',['dloff'=>'Schudule send','dlon'=>'Send now','dloff'=>'Schudule send','id'=>'send_now','name'=>'Ignore schuduled time and send now', 'checked'=>(isset($_GET['send_now']))])

@endif



<button onclick="submitJustCampign()"  class="btn btn-success mt-4">{{ __('Apply') }}</button>    

</div>