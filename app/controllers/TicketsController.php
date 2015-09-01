<?php

use Acme\Ticket;
use Finite\State\State;
use Finite\State\StateInterface;
use Finite\StateMachine\StateMachine;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;

class TicketsController extends \BaseController {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$tickets = Ticket::all();

		return View::make('tickets.index', compact('tickets'));
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		return View::make('tickets.create');
	}


	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		Ticket::create([
            'title' => Input::get('title'),
            'body' => Input::get('body'),
            'state' => Ticket::getInitialState(),
            'user_id' => 3
        ]);

        return Redirect::route('tickets.index');
	}




	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		$ticket = Ticket::findOrFail($id);

//        dd($ticket->lastTransition()->from);
        $possibleTransitions = $ticket->getCurrentState()->getTransitions();

        $transitions = [];
        foreach ($possibleTransitions as $transition) {
            $transitions[$transition] = Ticket::transitionLabel($transition);
        }



//        $ticket->apply('t12');
//        return $ticket->getAuditTrailName();
//        return $ticket->can('t12') ? 'yes': 'no'; // → false
//        return $ticket->getState();

//        $sm = new StateMachine();

        // Define states
//        $sm->addState(new State('s1', StateInterface::TYPE_INITIAL));
//        $sm->addState('s2');
//        $sm->addState('s3');
//        $sm->addState(new State('s4', StateInterface::TYPE_FINAL));

        // Define transitions
//        $sm->addTransition('t11', 's1', 's1');
//        $sm->addTransition('t12', 's1', 's2');
//        $sm->addTransition('t21', 's2', 's1');
//        $sm->addTransition('t23', 's2', 's3');
//        $sm->addTransition('t32', 's3', 's2');
//        $sm->addTransition('t34', 's3', 's4');

        // Initialize
//        $sm->setObject($ticket);
//        $sm->initialize();

        // Retrieve current state
//        return $sm->getCurrentState();

        // Can we process a transition ?
//        $sm->apply('draft');
//        return $sm->can('accept') ? 'Yes' : 'No';

        return View::make('tickets.edit', compact('ticket', 'transitions'));
	}


	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
        $ticket = Ticket::findOrFail($id);

        $input = Input::all();
        $transitionName = $input['transition'];
        $ticket->apply($transitionName);

//        $nextState = (string) $ticket->getStateMachine()->getTransition($transitionName)->getState();
//        $currentState = (string) $ticket->getCurrentState();
//        if ($currentState !== $nextState) {
//            $ticket->apply($transitionName);
//            if ($nextState == 'closed') {
//                $ticket->delete();
//
//                return Redirect::route('tickets.index');
//            }
//        }

        $ticket->update($input);

        return Redirect::route('tickets.index');
	}


	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		$ticket = Ticket::findOrFail($id);
        $ticket->delete();

        return Redirect::route('tickets.index');
	}


}
