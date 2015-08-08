<?php

namespace Acme;

use Finite\StatefulInterface;
use Illuminate\Database\Eloquent\SoftDeletingTrait;
use Illuminate\Support\Facades\Log;

class Ticket extends \Eloquent implements StatefulInterface
{

    use FiniteStateMachine, FiniteAuditTrail, SoftDeletingTrait;

    const
        STATE_INITIAL = 'draft',
        STATE_FINAL = 'complete';

    protected $table = 'tickets';

    protected $fillable = ['title', 'body', 'state', 'user_id'];

    protected $stateField = 'state';

    private $state;

    private $lastState;

    public static $counter = 0;

    public function __construct($attributes = [])
    {
        $this->initStateMachine();
        parent::__construct($attributes);
        $this->initAuditTrail();
    }

    public static function boot()
    {
        parent::boot();
        static::finiteAuditTrailBoot();
    }

    public static function getInitialState()
    {
        return static::STATE_INITIAL;
    }

    public static $statuses = [
        'ASS' => 'Assessing',
        'SRC' => 'Sourcing',
        'PRI' => 'Pricing',
        'CLS' => 'Closed'
    ];

    public static function transitionLabel($name)
    {
        $labels = [
            'save draft'                          => 'save draft',
            'submit request'                      => 'submit',
            'cancel draft'                        => 'cancel request',

            'reassign to requester'               => 'reassign to requester',
            'submit for sourcing'                 => 'submit for sourcing',
            'close request in review'             => 'close',

            'resubmit'                            => 'resubmit',
            'cancel request pending input'        => 'cancel request',

            'submit for proposal'                 => 'submit for proposal',
            'reassign to reviewer from sourcing'  => 'reassign to reviewer',
            'reassign to requester from sourcing' => 'reassign to requester',
            'close request in sourcing'           => 'close',

            'submit proposal'                     => 'submit proposal',
            'close request in proposal'           => 'close',

            'approve'                             => 'approve',
            'reject'                              => 'reject',
            'close request pending approval'      => 'close',
        ];

        return $labels[$name];
    }

    /**
     * @return array
     */
    protected function stateMachineConfig()
    {
        return [
            //'class'       => get_class(),//useful?
            'graph'       => 'TicketStateMachine',
            'states'      => [
                'draft'             => [
                    'type'       => 'initial',
                    'properties' => ['deletable' => true, 'editable' => true],
                ],
                'in review'         => [
                    'type'       => 'normal',
                    'properties' => ['deletable' => true, 'editable' => true],
                ],
                'pending input'     => [
                    'type'       => 'normal',
                    'properties' => ['deletable' => true, 'editable' => true],
                ],
                'resubmitted'       => [
                    'type'       => 'normal',
                    'properties' => [],
                ],
                'sourcing'          => [
                    'type'       => 'normal',
                    'properties' => ['deletable' => true, 'editable' => true],
                ],
                'drafting proposal' => [
                    'type'       => 'normal',
                    'properties' => ['deletable' => true, 'editable' => true],
                ],
                'pending approval'  => [
                    'type'       => 'normal',
                    'properties' => ['deletable' => true, 'editable' => true],
                ],
                'cataloguing'       => [
                    'type'       => 'normal',
                    'properties' => ['deletable' => true, 'editable' => true],
                ],
                'complete'          => [
                    'type'       => 'final',
                    'properties' => ['deletable' => false, 'editable' => true],
                ],
                'closed'            => [
                    'type'       => 'final',
                    'properties' => [],
                ]
            ],
            'transitions' => [
                'save draft'                          => ['from' => ['draft'], 'to' => 'draft'],
                'submit request'                      => ['from' => ['draft'], 'to' => 'in review'],
                'cancel draft'                        => ['from' => ['draft'], 'to' => 'closed'],

                'reassign to requester'               => ['from' => ['in review'], 'to' => 'pending input'],
                'submit for sourcing'                 => ['from' => ['in review'], 'to' => 'sourcing'],
                'close request in review'             => ['from' => ['in review'], 'to' => 'closed'],

                'resubmit'                            => ['from' => ['pending input'], 'to' => 'resubmitted'],
                'cancel request pending input'        => ['from' => ['pending input'], 'to' => 'closed'],

                'submit for proposal'                 => ['from' => ['sourcing'], 'to' => 'drafting proposal'],
                'reassign to reviewer from sourcing'  => ['from' => ['sourcing'], 'to' => 'in review'],
                'reassign to requester from sourcing' => ['from' => ['sourcing'], 'to' => 'pending input'],
                'close request in sourcing'           => ['from' => ['sourcing'], 'to' => 'closed'],

                'submit proposal'                     => ['from' => ['drafting proposal'], 'to' => 'pending approval'],
                'close request in proposal'           => ['from' => ['drafting proposal'], 'to' => 'close'],

                'approve'                             => ['from' => ['pending approval'], 'to' => 'complete'],
                'reject'                              => ['from' => ['pending approval'], 'to' => 'drafting proposal'],
                'close request pending approval'      => ['from' => ['pending approval'], 'to' => 'closed'],
            ],
            'callbacks'   => [
                'before' => [
                    ['on' => 'save draft', 'do' => [$this, 'beforeTransitionT12']],
                    ['on' => 'resubmit', 'do' => [$this, 'beforeResubmit']],
                    ['from' => 'in review', 'to' => 'sourcing', 'do' => function ($myStatefulInstance, $transitionEvent) {
//                        echo "Before callback from 's2' to 's3'";// debug
                        Log::info("Before callback from 's2' to 's3'");// debug
                    }],
                    ['from' => '-complete', 'to' => ['sourcing', 'drafting proposal'], 'do' => [$this, 'fromStatesS1S2ToS1S3']],
                ],
                'after'  => [
                    ['on' => 'resubmit', 'do' => [$this, 'afterResubmitted']],
                    ['from' => 'all', 'to' => 'all', 'do' => [$this, 'afterAllTransitions']],
                ],
            ],
        ];
    }

    public function beforeTransitionT12($myStatefulInstance, $transitionEvent)
    {
//        echo "Function called before transition: '".$transitionEvent->getTransition()->getName()."' !";// debug
        Log::info("Function called before transition: '" . $transitionEvent->getTransition()->getName() . "' !");
    }

    public function beforeResubmit($myStatefulInstance, $transitionEvent)
    {
        $this->lastState = $this->lastTransition()->from;
    }

    public function fromStatesS1S2ToS1S3()
    {
//        echo "Before callback from states 's1' or 's2' to 's1' or 's3'";// debug
        Log::info("Before callback from states 's1' or 's2' to 's1' or 's3'");// debug
    }

    public function afterAllTransitions($myStatefulInstance, $transitionEvent)
    {
        echo "After All Transitions !";// debug
        Log::info("After All Transitions !");// debug
    }

    public function afterResubmitted($myStatefulInstance, $transitionEvent)
    {
        Log::info("Function called after transition: '" . $transitionEvent->getTransition()->getName() . "' !");
        Log::info('Current state: ' . $myStatefulInstance->getCurrentState());
        if ($this->lastState === 'sourcing') {
            $myStatefulInstance->getStateMachine()->addTransition('reassign to sourcing', 'resubmitted', 'sourcing');
            $myStatefulInstance->apply('reassign to sourcing');
        }
        if ($this->lastState === 'in review') {
            $myStatefulInstance->getStateMachine()->addTransition('reassign to review', 'resubmitted', 'in review');
            $myStatefulInstance->apply('reassign to review');
        }
        Log::info('New state: ' . $myStatefulInstance->getCurrentState());
    }

    public function lastTransition()
    {
        $lastTransition = TicketStateTransition::where('ticket_id', $this->attributes['id'])->get()->last();

        return $lastTransition;
    }
}
