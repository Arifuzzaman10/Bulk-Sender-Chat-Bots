@extends('general.index', $setup)
@section('thead')
    <th>{{ __('Name') }}</th>
    <th>{{ __('Type') }}</th>
    <th>{{ __('crud.actions') }}</th>
@endsection
@section('tbody')
    @foreach ($setup['items'] as $item)
        <tr>
            <td>{{ $item->name }}</td>
            <td>{{ $item->type==1?__('Just a quick reply'):($item->type==2?__('Reply bot: On exact match'):__('Reply bot: When message contains')) }}</td>
            <td>
                <!-- EDIT -->
                <a href="{{ route('replies.edit',['reply'=>$item->id]) }}" class="btn btn-primary btn-sm">
                    <i class="ni ni-ruler-pencil"></i>
                </a>

                <!-- EDIT -->
                <a href="{{ route('replies.delete',['reply'=>$item->id]) }}" class="btn btn-danger btn-sm">
                    <i class="ni ni ni-fat-remove"></i>
                </a>
            </td>
        </tr> 
    @endforeach
@endsection