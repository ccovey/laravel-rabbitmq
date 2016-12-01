<?php

namespace Ccovey\LaravelRabbitMQ;

use Ccovey\RabbitMQ\Consumer\ConsumableParameters;
use Ccovey\RabbitMQ\Consumer\ConsumerInterface;
use Ccovey\RabbitMQ\Producer\Message;
use Ccovey\RabbitMQ\Producer\ProducerInterface;
use DateTime;
use Illuminate\Queue\Queue;
use Illuminate\Contracts\Queue\Queue as QueueContract;

class RabbitMqQueue extends Queue implements QueueContract
{
    /**
     * @var ConsumerInterface
     */
    private $consumer;

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var array
     */
    private $config;

    public function __construct(
        ConsumerInterface $consumer,
        ProducerInterface $producer,
        array $config
    )
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
        $this->pushRaw($this->createPayload($job, $data), $this->getQueueName($queue));
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

        $message = new Message(json_decode($payload, 1), $this->getQueueName($queue));
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
        $this->pushRaw($this->createPayload($job, $data), $this->getQueueName($queue), ['delay' => $delay]);
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
        $params = new ConsumableParameters($this->getQueueName($queue));
        $message = $this->consumer->getMessage($params);

        if (!$message) {
            return null;
        }

        return new RabbitMqJob($this->container, $this->producer, $this->consumer, $message);
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
        return $this->consumer->getSize($this->getQueueName($queue));
    }

    private function getQueueName($queue)
    {
        return $queue ?: 'queueName';
    }
}
