<?php

namespace Acme;


use Finite\StateMachine\StateMachine;

/**
 * The FiniteStateMachine Trait.
 * It provides easy ways to deal with Stateful objects and StateMachine
 * Prerequisite: install Finite package (https://github.com/yohang/Finite#readme)
 * Usage: in your Stateful Class, add the stateMachineConfig() protected method
 * and call initStateMachine() method at initialization (__contruct() method)
 *
 * @author Tortue Torche <tortuetorche@spam.me>
 */
trait FiniteStateMachine
{
    /**
     * @var \Finite\StateMachine\StateMachine
     */
    protected $stateMachine;

    /**
     * @var array
     */
    protected $finiteLoader;

    /**
     * @return array
     */
    protected abstract function stateMachineConfig();

//    public function newFromBuilder($attributes = [])
//    {
//        $instance = parent::newFromBuilder($attributes);
//        $instance->setFiniteState($instance->attributes['state']);
//        $instance->getStateMachine()->initialize();
////        $instance->initStateMachine();
//        return $instance;
//    }

    protected function initStateMachine(array $config = null)
    {
        static::$counter++;
        $this->finiteLoader = $config ?: $this->stateMachineConfig();
        $loader = new \Finite\Loader\ArrayLoader($this->finiteLoader);
        $sm = new StateMachine($this);

        $loader->load($sm);

        $sm->initialize();
        $this->stateMachine = $sm;
    }

    /**
     * Sets the object state
     *
     * @param string $state
     */
    public function setFiniteState($state)
    {
        $this->state = $state;
    }

    /**
     * Get the object state
     *
     * @return string
     */
    public function getFiniteState()
    {
        return $this->state;
    }

    /**
     * @return \Finite\StateMachine\StateMachine
     */
    public function getStateMachine()
    {
        return $this->stateMachine;
    }

    /**
     * @return \Finite\State\State
     */
    public function getCurrentState()
    {
        return $this->getStateMachine()->getCurrentState();
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->getCurrentState()->getName();
    }

    /**
     * @return string
     */
    public function getHumanState()
    {
        return $this->humanize($this->getState());
    }

    /**
     * @param string $transitionName
     *
     * @return string|null
     */
    public function getHumanStateTransition($transitionName)
    {
        $transitionIndex = array_search($transitionName, $this->getTransitions());
        if ($transitionIndex !== null && $transitionIndex !== false) {
            return $this->humanize(array_get($this->getTransitions(), $transitionIndex));
        }
    }

    /**
     * Returns if this state is the initial state
     *
     * @return boolean
     */
    public function isInitial()
    {
        return $this->getCurrentState()->isInitial();
    }

    /**
     * Returns if this state is the final state
     *
     * @return mixed
     */
    public function isFinal()
    {
        return $this->getCurrentState()->isFinal();
    }

    /**
     * Returns if this state is a normal state (!($this->isInitial() || $this->isFinal())
     *
     * @return mixed
     */
    public function isNormal()
    {
        return $this->getCurrentState()->isNormal();
    }

    /**
     * Returns the state type
     *
     * @return string
     */
    public function getType()
    {
        return $this->getCurrentState()->getType();
    }

    /**
     * @return array<string>
     */
    public function getTransitions()
    {
        return $this->getCurrentState()->getTransitions();
    }

    /**
     * @return array<string>
     */
    public function getProperties()
    {
        return $this->getCurrentState()->getProperties();
    }

    /**
     * @param array $properties
     */
    public function setProperties(array $properties)
    {
        $this->getCurrentState()->setProperties($properties);
    }

    /**
     * @param string $property
     *
     * @return bool
     */
    public function hasProperty($property)
    {
        return $this->getCurrentState()->has($property);
    }

    /**
     * @param string $targetState
     *
     * @return bool
     */
    public function is($targetState)
    {
        return $this->getState() === $targetState;
    }

    /**
     * @param string $transitionName
     *
     * @return bool
     */
    public function can($transitionName)
    {
        return $this->getStateMachine()->can($transitionName);
    }

    /**
     * @param string $transitionName
     *
     * @return mixed
     *
     * @throws \Finite\Exception\StateException
     */
    public function apply($transitionName)
    {
        return $this->getStateMachine()->apply($transitionName);
    }

    /**
     * @param callable $callback
     * @param array    $spec
     */
    public function addBefore($callback, array $spec = [])
    {
        $callbackHandler = new \Finite\Event\CallbackHandler($this->getStateMachine()->getDispatcher());
        $callbackHandler->addBefore($this->getStateMachine(), $callback, $spec);
    }

    /**
     * @param callable $callback
     * @param array    $spec
     */
    public function addAfter($callback, array $spec = [])
    {
        $callbackHandler = new \Finite\Event\CallbackHandler($this->getStateMachine()->getDispatcher());
        $callbackHandler->addAfter($this->getStateMachine(), $callback, $spec);
    }

    /**
     * @param callable $callback
     * @param array    $spec
     */
    public function prependBefore($callback, array $spec = [])
    {
        $config = $this->finiteLoader;
        array_set($config, 'callbacks.before', array_merge(
            [array_merge($spec, ['do' => $callback])],
            array_get($config, 'callbacks.before', [])
        ));
        $this->initStateMachine($config);
    }

    /**
     * @param callable $callback
     * @param array    $spec
     */
    public function prependAfter($callback, array $spec = [])
    {
        $config = $this->finiteLoader;
        array_set($config, 'callbacks.after', array_merge(
            [array_merge($spec, ['do' => $callback])],
            array_get($config, 'callbacks.after', [])
        ));
        $this->initStateMachine($config);
    }

    /**
     * Find and return the Initial state if exists
     *
     * @return string
     *
     * @throws Exception\StateException
     */
    public function findInitialState()
    {
        foreach ($this->getStateMachine()->getStates() as $state) {
            $state = $this->getStateMachine()->getState($state);
            if (\Finite\State\State::TYPE_INITIAL === $state->getType()) {
                return $state->getName();
            }
        }

        throw new \Finite\Exception\StateException('No initial state found.');
    }

    /**
     *
     * @param  string $attribute Attribute name who contains transition name
     * @param  string $errorMessage
     * @return mixed  Returns false if there are errors
     */
    public function applyStateTransition($attribute = null, $errorMessage = null)
    {
        $attribute = $attribute ?: 'state_transition';
        $attributes = $this->getAttributes();
        if (($stateTransition = array_get($attributes, $attribute))) {
            if ($this->can($stateTransition)) {
                unset($this->$attribute);
                $this->apply($stateTransition);
            } else {
                $defaultErrorMessage = sprintf(
                    'The "%s" transition can not be applied to the "%s" state.',
                    $stateTransition,
                    $this->getState()
                );
                \Log::error($defaultErrorMessage);
                $errorMessage = $errorMessage ?: $defaultErrorMessage;
                if (method_exists($this, 'errors')) {
                    $this->errors()->add($attribute, $errorMessage);
                } else {
                    throw new \Exception($errorMessage, 1);
                }

                return false;
            }
        }
    }

    /**
     * $this->humanize("my beautiful hat");//-> 'My beautiful hat'
     *
     * @param  string $value
     * @return string
     */
    protected function humanize($value)
    {
        return ucfirst(snake_case(camel_case($value), ' '));
    }
}