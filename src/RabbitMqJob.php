<?php

namespace Ccovey\LaravelRabbitMQ;

use Ccovey\RabbitMQ\Producer\Message;
use Ccovey\RabbitMQ\Producer\Producer;
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
    private $container;

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var QueuedMessage
     */
    private $message;

    public function __construct(Container $container, Producer $producer, QueuedMessage $message)
    {
        $this->container = $container;
        $this->producer = $producer;
        $this->message = $message;
    }

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire()
    {
        $this->resolveAndFire($this->message->getBody());
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return 1;
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        $this->message->getBody();
    }

    public function delete()
    {
        parent::delete();
        $this->producer->getChannel()->acknowledge($this->message->delivery_tag);
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
    }
}
