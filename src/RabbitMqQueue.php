<?php

namespace Ccovey\LaravelRabbitMQ;

use Ccovey\RabbitMQ\Consumer\ConsumableParameters;
use Ccovey\RabbitMQ\Consumer\Consumer;
use Ccovey\RabbitMQ\Producer\Message;
use Ccovey\RabbitMQ\Producer\Producer;
use DateTime;
use Illuminate\Queue\Queue;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class RabbitMqQueue extends Queue implements QueueContract
{
    /**
     * @var Consumer
     */
    private $consumer;

    /**
     * @var Producer
     */
    private $producer;

    /**
     * @var array
     */
    private $config;

    public function __construct(Consumer $consumer, Producer $producer, array $config)
    {
        $this->consumer = $consumer;
        $this->producer = $producer;
        $this->config = $config;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string $job
     * @param  mixed  $data
     * @param  string $queue
     *
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        $this->pushRaw($this->createPayload($job, $data), $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string $payload
     * @param  string $queue
     * @param  array  $options
     *
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        if (isset($options['delay'])) {
            $payload['scheduledAt'] = new DateTime(sprintf('+%s', $options['delay']));
        }

        $message = new Message($payload, $queue);
        $this->producer->publish($message);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTime|int $delay
     * @param  string        $job
     * @param  mixed         $data
     * @param  string        $queue
     *
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        $this->pushRaw($this->createPayload($job, $data), $queue, ['delay' => $delay]);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string $queue
     *
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $params = new ConsumableParameters($queue);
        $message = $this->consumer->getMessage($params);

        if (!$message) {
            return null;
        }

        return new RabbitMqJob($this->container, $this->producer, $message);
    }

    /**
     * Get the size of the queue.
     *
     * @param  string $queue
     *
     * @return int
     */
    public function size($queue = null)
    {
        return $this->consumer->getSize($queue);
    }
}
