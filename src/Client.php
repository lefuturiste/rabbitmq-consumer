<?php

namespace Lefuturiste\RabbitMQConsumer;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
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

    /**
     * @var null
     */
    private $rootValue = null;

    /**
     * The name of the queue used to virtualize events
     *
     * @var string
     */
    private $queue = 'default';

    /**
     * Array of listeners
     *
     * @var array
     */
    private $listeners = [];

    public function __construct(AMQPStreamConnection $connexion, $exchange = 'router', $queue = 'default')
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
     * Add a listener to a specific event
     *
     * @param string $event
     * @param callable $callback
     */
    public function addListener(string $event, callable $callback = null): void
    {
        $this->listeners[$event] = $callback;
    }

    public function onEvent(string $event, $body, callable $callback): void
    {
        echo "\n--------\n";
        echo "Received message...";
        echo "\n event: {$event}";
        echo "\n sent to: {$callback[0]} {$callback[1]} \n";
        call_user_func($callback, $body, $this->rootValue);
        echo "\n--------\n";
    }

    /**
     * Start the daemon
     *
     * @return void
     */
    public function listen(): void
    {
        $this->channel->queue_declare($this->queue, false, true, false, false);
        $this->channel->exchange_declare($this->exchange, 'direct', false, true, false);
        $this->channel->queue_bind($this->queue, $this->exchange);
        $this->channel->basic_consume(
            $this->queue,
            "consumer",
            false,
            false,
            false,
            false,
            function (AMQPMessage $message) {
                $body = json_decode($message->body, 1);
                $callback = $this->listeners[$body['event']];
                $this->onEvent($body['event'], $body['body'], $callback);
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
                if ($message->body === 'quit') {
                    $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
                }
            }
        );

        register_shutdown_function(function ($channel, $connection) {
            $channel->close();
            $connection->close();
        }, $this->channel, $this->connexion);

        echo "\n - Listening... \n";

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
