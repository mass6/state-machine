@extends('main')

@section('content')

    <h3>Tickets</h3>

    @if(count($tickets))
        <table class="table table-striped table-hover">
            <thead>
                <th>id</th>
                <th>Title</th>
                <th>State</th>
                <th>Created</th>
                <th>Actions</th>
            </thead>
            <tbody>

                @foreach($tickets as $ticket)
                    <tr>
                        <td>{{ $ticket->id }}</td>
                        <td><a href="/tickets/{{$ticket->id}}/edit">{{ $ticket->title }}</a></td>
                        <td>{{ $ticket->getProperties()['label'] }}</td>
                        <td>{{ $ticket->created_at }}</td>
                        <td>
                            {{ Form::open(['route' => ['tickets.destroy', $ticket->id], 'method' => 'DELETE']) }}
                                    <!-- Submit button -->
                            {{ Form::submit('Delete', ['class' => 'btn btn-danger', 'id' => 'Delete_button']) }}

                            {{ Form::close() }}
                        </td>
                    </tr>
                @endforeach
            </tbody>


        </table>

        <div>
            <a href="{{ route('tickets.create') }}" class="btn btn-primary">New Ticket</a>
        </div>
    @endif
@stop