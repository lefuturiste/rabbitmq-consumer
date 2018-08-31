<?php
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\TestCase;
use Lefuturiste\RabbitMQConsumer\Client;
class ConsumerTest extends TestCase {
	public function getClient()
	{
		return new Client(
			new AMQPStreamConnection('example.com', '424242', 'user', 'password', 'virtual host')
		);
	}
	public function testPublish()
	{
		$client = $this->getClient();
    $client->addListener('my_event', function(array $message){
        var_dump($message);
        echo "\n";
    });
    $client->listen();
	}
}
