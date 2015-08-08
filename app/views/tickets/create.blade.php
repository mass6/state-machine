@extends('main')

@section('content')


    <div class="row">
        <h3>New Ticket</h3>

        {{ Form::open(array('route' => 'tickets.store')); }}

        <!-- Title Form Input -->
        {{ Form::label('title', 'Title:') }}
        {{ Form::text('title', null, ['class' => 'form-control', 'id' => 'title_input']) }}

        <!-- Body Form Input -->
        {{ Form::label('body', 'Body:') }}
        {{ Form::textarea('body', null, ['class' => 'form-control']) }}

        <!-- Submit button -->

        <br/>
        {{ Form::submit('Submit', ['class' => 'btn btn-primary', 'id' => 'Submit_button']) }}
        <a href="{{ route('tickets.index') }}" class="btn btn-info">Cancel</a>
        {{ Form::close() }}

    </div>


@stop