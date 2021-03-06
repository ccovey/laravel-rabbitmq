<?php

namespace Ccovey\LaravelRabbitMQ;

use Ccovey\RabbitMQ\Config\QueueConfig;
use Ccovey\RabbitMQ\Connection\Connection;
use Ccovey\RabbitMQ\Connection\ConnectionParameters;
use Ccovey\RabbitMQ\Consumer\Consumer;
use Ccovey\RabbitMQ\Producer\Producer;
use Ccovey\RabbitMQ\QueueDeclarer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;

class RabbitMqProvider extends ServiceProvider
{
    public function register()
    {
        $rabbitMqConfig = $this->app['config']['queue.connections.rabbitmq'];
        $this->app->singleton(Connection::class, function(Application $app) use ($rabbitMqConfig) {
            $params = new ConnectionParameters(
                $rabbitMqConfig['host'],
                $rabbitMqConfig['port'] ?? ConnectionParameters::DEFAULT_PORT,
                $rabbitMqConfig['user'] ?? ConnectionParameters::DEFAULT_USER,
                $rabbitMqConfig['password'] ?? ConnectionParameters::DEFAULT_PASSWORD,
                $rabbitMqConfig['vhost'] ?? '/',
                $rabbitMqConfig['insist'] ?? false,
                $rabbitMqConfig['loginMethod'] ?? ConnectionParameters::LOGIN_METHOD,
                $rabbitMqConfig['loginResponse'] ?? null,
                $rabbitMqConfig['locale'] ?? ConnectionParameters::LOCALE,
                $rabbitMqConfig['connectionTimeout'] ?? ConnectionParameters::CONNECTION_TIMEOUT,
                $rabbitMqConfig['readWriteTimeout'] ?? ConnectionParameters::READ_WRITE_TIMEOUT,
                $rabbitMqConfig['context'] ?? null,
                $rabbitMqConfig['keepalive'] ?? false,
                $rabbitMqConfig['heartbeat'] ?? 0
            );
            return new Connection($params);
        });

        $this->app->singleton(QueueConfig::class, function (Application $app) use ($rabbitMqConfig) {
            return new QueueConfig($rabbitMqConfig['queue'] ?? []);
        });

        $this->app->singleton(QueueDeclarer::class, function (Application $app) use ($rabbitMqConfig) {
            return new QueueDeclarer($app[Connection::class], $app[QueueConfig::class]);
        });

        $this->app->singleton(Consumer::class, function(Application $app) {
            return new Consumer($app[Connection::class], $app[QueueDeclarer::class]);
        });

        $this->app->singleton(Producer::class, function(Application $app) {
            return new Producer($app[Connection::class], $app[QueueDeclarer::class]);
        });

        $this->app->booted(function () use ($rabbitMqConfig) {
            $queueManager = $this->app[QueueManager::class];
            $queueManager->addConnector('rabbitmq', function() use ($rabbitMqConfig) {
                return new RabbitMQConnector(
                    $this->app[Consumer::class],
                    $this->app[Producer::class],
                    $rabbitMqConfig);
            });
        });
    }
}
