<?php

namespace Ccovey\LaravelRabbitMQ;

use Ccovey\RabbitMQ\Consumer\ConsumerInterface;
use Ccovey\RabbitMQ\Producer\Message;
use Ccovey\RabbitMQ\Producer\ProducerInterface;
use Ccovey\RabbitMQ\QueuedMessage;
use DateTime;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;

class RabbitMqJob extends Job implements JobContract
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var ConsumerInterface
     */
    private $consumer;

    /**
     * @var QueuedMessage
     */
    private $message;

    public function __construct(Container $container, ProducerInterface $producer, ConsumerInterface $consumer, QueuedMessage $message)
    {
        $this->container = $container;
        $this->producer = $producer;
        $this->consumer = $consumer;
        $this->message = $message;
        $this->queue = $this->message->getQueueName();
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return $this->message->attempts ?? 0;
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->message->getRawBody();
    }

    public function delete()
    {
        parent::delete();
        $this->consumer->getChannel()->acknowledge($this->message->getDeliveryTag());
    }

    public function release($delay = 0)
    {
        parent::release($delay);
        $body = $this->message->getBody();

        if ($delay > 0) {
            $body['scheduledAt'] = new DateTime(sprintf('+%s', $delay));
        }

        $body['attempts'] = isset($body['attempts']) ? $body['attempts'] + 1 : 1;
        $message = new Message($body, $this->message->getQueueName());

        $this->producer->publish($message);
        $this->consumer->getChannel()->acknowledge($this->message->getDeliveryTag());
    }
}
