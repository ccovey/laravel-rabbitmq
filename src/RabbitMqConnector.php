<?php

namespace Ccovey\LaravelRabbitMQ;

use Ccovey\RabbitMQ\Connection\ConnectionInterface;
use Ccovey\RabbitMQ\Consumer\Consumer;
use Ccovey\RabbitMQ\Consumer\ConsumerInterface;
use Ccovey\RabbitMQ\ExchangeDeclarer;
use Ccovey\RabbitMQ\Producer\Producer;
use Ccovey\RabbitMQ\Producer\ProducerInterface;
use Ccovey\RabbitMQ\QueueDeclarer;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Connectors\ConnectorInterface;

class RabbitMqConnector implements ConnectorInterface
{
    /**
     * @var ConsumerInterface
     */
    private $consumer;

    /**
     * @var ProducerInterface
     */
    private $producer;

    /** @var array */
    private $configuration;

    public function __construct(
        ConsumerInterface $consumer,
        ProducerInterface $producer,
        array $configuration
    )
    {
        $this->consumer = $consumer;
        $this->producer = $producer;
        $this->configuration = $configuration;
    }

    public function connect(array $config) : Queue
    {
        return new RabbitMqQueue($this->consumer, $this->producer, $this->configuration);
    }
}
