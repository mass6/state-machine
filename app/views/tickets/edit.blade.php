@extends('main')

@section('content')


    <div class="row">
        <h3>Edit Ticket</h3>

        {{ Form::model($ticket, ['route' => ['tickets.update', $ticket->id ], 'method' => 'PATCH']); }}

        <!-- Title Form Input -->
        {{ Form::label('title', 'Title:') }}
        {{ Form::text('title', null, ['class' => 'form-control', 'id' => 'title_input']) }}

        <!-- Body Form Input -->
        {{ Form::label('body', 'Body:') }}
        {{ Form::textarea('body', null, ['class' => 'form-control']) }}

        <!-- Status Form Input -->
        {{ Form::label('transition', 'Transition:') }}
        {{ Form::select('transition', $transitions, null, ['class' => 'form-control', 'id' => 'transition_input']) }}


        <!-- Submit button -->

        <br/>
        {{ Form::submit('Submit', ['class' => 'btn btn-primary', 'id' => 'Submit_button']) }}
        {{ Form::close() }}

    </div>


@stop