<?php

namespace Acme;

use Finite\StateMachine\StateMachine;

/**
 * The FiniteAuditTrail Trait.
 * This plugin, for the Finite package (see https://github.com/yohang/Finite), adds support for keeping an audit trail for any state machine.
 * Having an audit trail gives you a complete history of the state changes in your stateful model.
 * Prerequisites:
 *   1. Install Finite package (https://github.com/yohang/Finite#readme)
 *   2. Use FiniteStateMachine in your model (https://gist.github.com/tortuetorche/6365575)
 * Usage: in your Stateful Class use this trait after FiniteStateMachine trait, like this "use FiniteAuditTrail;".
 * Then call initAuditTrail() method at the end of initialization (__contruct() method) after initStateMachine() and parent::__construct() call.
 * Finally create or complete the static boot() method in your model like this:
 *
 *   class MyStatefulModel extends Eloquent implements Finite\StatefulInterface
 *   {
 *
 *       use FiniteStateMachine;
 *       use FiniteAuditTrail;
 *
 *       public static function boot()
 *       {
 *           parent::boot();
 *           static::finiteAuditTrailBoot();
 *       }
 *
 *       public function __construct($attributes = [])
 *       {
 *           $this->initStateMachine();
 *           parent::__construct($attributes);
 *           $this->initAuditTrail();
 *       }
 *   }
 *
 * Optionally in your AuditTrail model, you can create a $statefulModel property to access the StateMachine model who's audited:
 *
 *   class MyStatefulModelStateTransition extends Eloquent
 *   {
 *       public $statefulModel;
 *   }
 *
 * @author Tortue Torche <tortuetorche@spam.me>
 */
trait FiniteAuditTrail
{
    public static function finiteAuditTrailBoot()
    {
        static::saveInitialState();
    }

    protected static function saveInitialState()
    {
//        dd('save');
        static::created(function ($model) {
            $transition = new \Finite\Transition\Transition(null, null, $model->findInitialState());
            $model->storeAuditTrail($model, $transition, false);
        });
    }

    protected $auditTrailModel;
    protected $auditTrailName;
    protected $auditTrailAttributes;// We can't set an empty array as default value here, maybe a PHP Trait bug ?

    /**
     * @param  mixed|array|args $args
     * if $args is an array:
     *   initAuditTrail(['to' => 'ModelAuditTrail', 'attributes' => ['name', 'email'], 'prepend' => true]);
     * else: initAuditTrail('ModelAuditTrail', ['name', 'email']);
     *   first param: string $to Model name who stores the history
     *   second param: string|array $attributes Attribute(s) or method(s) from stateful model to save
     */
    protected function initAuditTrail($args = null)
    {
        // Default options
        $options = [ 'attributes' => (array) $this->auditTrailAttributes, 'to' => "\\".get_called_class()."StateTransition" ];
//        dd($options);


        if (func_num_args() === 2) {
            $args = func_get_args();
            list($options['to'], $attributes) = $args;
            $newOptions = array_extract_options($attributes);
            $options['attributes'] = $attributes;
            $options = array_merge($options, $newOptions);
        } elseif (func_num_args() === 1) {
            if (is_array($args)) {
                $newOptions = array_extract_options($args);
                if (empty($newOptions)) {
                    $options['attributes'] = $args;
                } else {
                    $options = array_merge($options, $newOptions);
                }
            } elseif (is_string($args)) {
                $options['to'] = $args;
            }
        }
//        dd($options['to']);
        $this->auditTrailName =  $options['to'];

        if (array_get($options, 'prepend') === true) {
            // Audit trail State Machine changes at the first 'after' transition
            $this->auditTrailAttributes = (array) $options['attributes'];
            $this->auditTrailName =  $options['to'];
            $this->prependAfter([$this, 'storeAuditTrail']);
        } else {
            // Audit trail State Machine changes at the last 'after' transition
            $this->addAfter([$this, 'storeAuditTrail']);
        }

    }


    /**
     * Create a new model instance that is existing.
     *
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function newFromBuilder($attributes = [])
    {
        $instance = parent::newFromBuilder($attributes);
        $instance->setFiniteState($instance->attributes['state']);
        $this->restoreAuditTrail($instance);
        return $instance;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model|static $instance
     */
    protected function restoreAuditTrail($instance)
    {
        // Initialize the StateMachine when the $instance is loaded from the database and not created via __construct() method
        $instance->getStateMachine()->initialize();
    }

    /**
     * @param  object  $self
     * @param  \Finite\Event\TransitionEvent|Finite\Transition\Transition $transitionEvent
     * @param  boolean $save Optional, default: true
     */
    public function storeAuditTrail($self, $transitionEvent, $save = true)
    {
//        dd($self);
        // Save State Machine model to log initial state
        if ($save === true || $this->exists === false) {
            $this->save();
        }
        if (is_a($transitionEvent, "\Finite\Event\TransitionEvent")) {
            $transition = $transitionEvent->getTransition();
        } else {
            $transition = $transitionEvent;
        }

        $this->auditTrailModel = \App::make($this->auditTrailName);
        if (property_exists($this->auditTrailModel, 'statefulModel')) {
            $this->auditTrailModel->statefulModel = $this;
        }

        $values = [];
        $values['event'] = $transition->getName();
        $initialStates = $transition->getInitialStates();
        if (! empty($initialStates)) {
            $values['from'] = $transitionEvent->getInitialState()->getName();
        }

        $values['to'] = $transition->getState();

        $statefulName = $this->auditTrailModel->statefulName ?: snake_case(str_singular($this->getTable()));
        $values[$statefulName.'_id'] = $this->getKey();//Foreign key

        $statefulType = $statefulName.'_type';
        $columnNames = column_names($this->auditTrailModel->getTable());
        if (in_array($statefulType, $columnNames)) {
            $values[$statefulType] = get_class($this);//For morph relation
        }

        // TODO: Fill and save additional attributes in a created()/afterCreate() model event
        foreach ((array) $this->auditTrailAttributes as $attribute) {
            if ($this->getAttribute($attribute)) {
                $values[$attribute] = $this->getAttribute($attribute);
            }
        }

        $this->auditTrailModel->fill($values);
        $validated = $this->auditTrailModel->save();

        if (! $validated) {
            // TODO: Use this $validationErrors var in the Exception class
            // $validationErrors = '<ul>'.implode('', array_values($this->auditTrailModel->errors()->all('<li>:message</li>'))).'</ul>';

            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Unable to save auditTrail model '".$this->auditTrailName."'");
        }
    }

    public function getAuditTrailModel()
    {
        return $this->auditTrailModel;
    }

    public function getAuditTrailName()
    {
        return $this->auditTrailName;
    }
}