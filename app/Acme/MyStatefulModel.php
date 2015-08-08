<?php

namespace Acme;

use Finite\StatefulInterface;

class MyStatefulModel extends \Eloquent implements StatefulInterface
{

    use FiniteStateMachine;
    use FiniteAuditTrail;

    public static function boot()
    {
        parent::boot();
        static::finiteAuditTrailBoot();
    }

    public function __construct($attributes = [])
    {
        $this->initStateMachine();
        parent::__construct($attributes);
        $this->initAuditTrail();
    }

    protected function stateMachineConfig()
    {
        return [
            'states'      => [
                's1' => [
                    'type'       => 'initial',
                    'properties' => ['deletable' => true, 'editable' => true],
                ],
                's2' => [
                    'type'       => 'normal',
                    'properties' => [],
                ],
                's3' => [
                    'type'       => 'final',
                    'properties' => [],
                ]
            ],
            'transitions' => [
                't12' => ['from' => ['s1'], 'to' => 's2'],
                't23' => ['from' => ['s2'], 'to' => 's3'],
                't21' => ['from' => ['s2'], 'to' => 's1'],
            ],
            'callbacks'   => [
                'before' => [
                    ['on' => 't12', 'do' => [$this, 'beforeTransitionT12']],
                    ['from' => 's2', 'to' => 's3', 'do' => function ($myStatefulInstance, $transitionEvent) {
                        echo "Before callback from 's2' to 's3'";// debug
                    }],
                    ['from' => '-s3', 'to' => ['s3', 's1'], 'do' => [$this, 'fromStatesS1S2ToS1S3']],
                ],
                'after'  => [
                    ['from' => 'all', 'to' => 'all', 'do' => [$this, 'afterAllTransitions']],
                ],
            ],
        ];
    }

    public function beforeTransitionT12($myStatefulInstance, $transitionEvent)
    {
        echo "Function called before transition: '" . $transitionEvent->getTransition()->getName() . "' !";// debug
    }

    public function fromStatesS1S2ToS1S3()
    {
        echo "Before callback from states 's1' or 's2' to 's1' or 's3'";// debug
    }

    public function afterAllTransitions($myStatefulInstance, $transitionEvent)
    {
        echo "After All Transitions !";// debug
    }


    /**
     * Sets the object state
     *
     * @return string
     */
    public function getFiniteState()
    {
        // TODO: Implement getFiniteState() method.
    }

    /**
     * Sets the object state
     *
     * @param string $state
     */
    public function setFiniteState($state)
    {
        // TODO: Implement setFiniteState() method.
    }
}
