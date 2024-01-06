@extends('general.index', $setup)
@section('thead')
    <th>{{ __('Name') }}</th>
    <th>{{ __('Phone') }}</th>
    <th>{{ __('Create at') }}</th>
    <th>{{ __('crud.actions') }}</th>
@endsection
@section('tbody')
    @foreach ($setup['items'] as $item)
        <tr>
            <td>{{ $item->name }}</td>
            <td>{{ $item->phone }}</td>
            <td>{{ $item->created_at->format('Y-m-d') }}</td>
            <td>
                <!-- CHAT -->
                <a href="{{ route('campaigns.create',['contact_id'=>$item->id]) }}" class="btn btn-outline-success btn-sm">
                    <span class="btn-inner--icon"><i class="ni ni-chat-round"></i></span>
                    <span class="btn-inner--text">{{ __('Start chat')}}</span>
                </a>

                <!-- EDIT -->
                <a href="{{ route('contacts.edit',['contact'=>$item->id]) }}" class="btn btn-primary btn-sm">
                    <i class="ni ni-ruler-pencil"></i>
                </a>

                <!-- EDIT -->
                <a href="{{ route('contacts.delete',['contact'=>$item->id]) }}" class="btn btn-danger btn-sm">
                    <i class="ni ni ni-fat-remove"></i>
                </a>
            </td>
        </tr> 
    @endforeach
@endsection