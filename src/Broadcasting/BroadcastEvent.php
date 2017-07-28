<?php

namespace Nova\Broadcasting;


use Nova\Queue\Job;
use Nova\Support\Contracts\ArrayableInterface;
use Nova\Broadcasting\Contracts\BroadcasterInterface;

use ReflectionClass;
use ReflectionProperty;


class BroadcastEvent
{
    /**
     * The broadcaster implementation.
     *
     * @var \Nova\Broadcasting\Contracts\BroadcasterInterface
     */
    protected $broadcaster;


    /**
     * Create a new job handler instance.
     *
     * @param  \Nova\Broadcasting\Contracts\BroadcasterInterface  $broadcaster
     * @return void
     */
    public function __construct(Broadcaster $broadcaster)
    {
        $this->broadcaster = $broadcaster;
    }

    /**
     * Handle the queued job.
     *
     * @param  \Nova\Queue\Job  $job
     * @param  array  $data
     * @return void
     */
    public function fire(Job $job, array $data)
    {
        $event = unserialize($data['event']);

        $name = method_exists($event, 'broadcastAs') ? $event->broadcastAs() : get_class($event);

        $this->broadcaster->broadcast(
            $event->broadcastOn(), $name, $this->getPayloadFromEvent($event)
        );

        $job->delete();
    }

    /**
     * Get the payload for the given event.
     *
     * @param  mixed  $event
     * @return array
     */
    protected function getPayloadFromEvent($event)
    {
        if (method_exists($event, 'broadcastWith')) {
            return $event->broadcastWith();
        }

        $payload = array();

        //
        $properties = with(new ReflectionClass($event))->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $name = $property->getName();

            $payload[$name] = $this->formatProperty($property->getValue($event));
        }

        return $payload;
    }

    /**
     * Format the given value for a property.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function formatProperty($value)
    {
        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        return $value;
    }
}
