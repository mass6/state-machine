<?php

namespace Acme;

use Finite\StatefulInterface;
use Illuminate\Database\Eloquent\SoftDeletingTrait;
use Illuminate\Support\Facades\Log;

class Ticket extends \Eloquent implements StatefulInterface
{

    use FiniteStateMachine, FiniteAuditTrail, SoftDeletingTrait;

    const
        STATE_INITIAL = 'DRA',
        STATE_FINAL = 'COM';

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
                'DRA'             => [
                    'type'       => 'initial',
                    'properties' => ['label' => 'Draft', 'deletable' => true, 'editable' => true],
                ],
                'REV'         => [
                    'type'       => 'normal',
                    'properties' => ['label' => 'In Review', 'deletable' => true, 'editable' => true],
                ],
                'INP'     => [
                    'type'       => 'normal',
                    'properties' => ['label' => 'Pending Input', 'deletable' => true, 'editable' => true],
                ],
                'RSB'       => [
                    'type'       => 'normal',
                    'properties' => ['label' => 'Resubmitted'],
                ],
                'SRC'          => [
                    'type'       => 'normal',
                    'properties' => ['label' => 'Sourcing', 'deletable' => true, 'editable' => true],
                ],
                'PRP' => [
                    'type'       => 'normal',
                    'properties' => ['label' => 'Drafting Proposal', 'deletable' => true, 'editable' => true],
                ],
                'APP'  => [
                    'type'       => 'normal',
                    'properties' => ['label' => 'Pending Approval', 'deletable' => true, 'editable' => true],
                ],
                'CAT'       => [
                    'type'       => 'normal',
                    'properties' => ['label' => 'Cataloguing', 'deletable' => true, 'editable' => true],
                ],
                'COM'          => [
                    'type'       => 'final',
                    'properties' => ['label' => 'Complete', 'deletable' => false, 'editable' => true],
                ],
                'CLS'            => [
                    'type'       => 'final',
                    'properties' => ['label' => 'Closed'],
                ]
            ],
            'transitions' => [
                'save draft'                          => ['from' => ['DRA'], 'to' => 'DRA'],
                'submit request'                      => ['from' => ['DRA'], 'to' => 'REV'],
                'cancel draft'                        => ['from' => ['DRA'], 'to' => 'CLS'],

                'reassign to requester'               => ['from' => ['REV'], 'to' => 'INP'],
                'submit for sourcing'                 => ['from' => ['REV'], 'to' => 'SRC'],
                'close request in review'             => ['from' => ['REV'], 'to' => 'CLS'],

                'resubmit'                            => ['from' => ['INP'], 'to' => 'RSB'],
                'cancel request pending input'        => ['from' => ['INP'], 'to' => 'CLS'],

                'submit for proposal'                 => ['from' => ['SRC'], 'to' => 'PRP'],
                'reassign to reviewer from sourcing'  => ['from' => ['SRC'], 'to' => 'REV'],
                'reassign to requester from sourcing' => ['from' => ['SRC'], 'to' => 'INP'],
                'close request in sourcing'           => ['from' => ['SRC'], 'to' => 'CLS'],

                'submit proposal'                     => ['from' => ['PRP'], 'to' => 'APP'],
                'close request in proposal'           => ['from' => ['PRP'], 'to' => 'CLS'],

                'approve'                             => ['from' => ['APP'], 'to' => 'COM'],
                'reject'                              => ['from' => ['APP'], 'to' => 'PRP'],
                'close request pending approval'      => ['from' => ['APP'], 'to' => 'CLS'],
            ],
            'callbacks'   => [
                'before' => [
                    ['on' => 'save draft', 'do' => [$this, 'beforeTransitionT12']],
                    ['on' => 'resubmit', 'do' => [$this, 'beforeResubmit']],
                    ['from' => 'REV', 'to' => 'SRC', 'do' => function ($myStatefulInstance, $transitionEvent) {
//                        echo "Before callback from 's2' to 's3'";// debug
                        Log::info("Before callback from 's2' to 's3'");// debug
                    }],
                    ['from' => '-complete', 'to' => ['SRC', 'PRP'], 'do' => [$this, 'fromStatesS1S2ToS1S3']],
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
        if ($this->lastState === 'SRC') {
            $myStatefulInstance->getStateMachine()->addTransition('reassign to sourcing', 'RSB', 'SRC');
            $myStatefulInstance->apply('reassign to sourcing');
        }
        if ($this->lastState === 'REV') {
            $myStatefulInstance->getStateMachine()->addTransition('reassign to review', 'RSB', 'REV');
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
