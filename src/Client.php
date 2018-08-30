<?php

namespace Lefuturiste\RabbitMQConsumer;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Client
{
    /**
     * @var AMQPStreamConnection
     */
    public $connexion;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var string
     */
    private $exchange;

    private $rootValue = null;

    public function __construct(AMQPStreamConnection $connexion, $exchange = 'router')
    {
        $this->connexion = $connexion;
        $this->exchange = $exchange;
        $this->channel = $this->connexion->channel();
    }

    /**
     * Set the root value
     *
     * @param $rootValue mixed
     */
    public function setRootValue($rootValue): void
    {
        $this->rootValue = $rootValue;
    }

    /**
     * Add a listener to a specific queue or event
     *
     * @param string $queue
     * @param callable $callback
     */
    public function addListener(string $queue, callable $callback = null): void
    {
        $this->channel->queue_declare($queue, false, true, false, false);
        $this->channel->exchange_declare($this->exchange, 'direct', false, true, false);
        $this->channel->queue_bind($queue, $this->exchange);
        $this->channel->basic_consume(
            $queue,
            'consumer',
            false,
            false,
            false,
            false,
            function (AMQPMessage $message) use ($callback) {
                echo "\n--------\n";
                echo "Received message...";
                call_user_func($callback, json_decode($message->body, 1), $this->rootValue);
                echo "\n--------\n";
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                if ($message->body === 'quit') {
                    $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
                }
            });
    }

    /**
     * Start the daemon
     *
     * @return void
     */
    public function listen(): void
    {
        register_shutdown_function(function ($channel, $connection) {
            $channel->close();
            $connection->close();
        }, $this->channel, $this->connexion);
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    /**
     * @return AMQPChannel
     */
    public function getChannel(): AMQPChannel
    {
        return $this->channel;
    }
}
