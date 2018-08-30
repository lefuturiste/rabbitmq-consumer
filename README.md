# RabbitMQ Consumer

The simplest way to consume RabbitMQ messages.

##  Installation

Using composer dependencies manager:

`composer require lefuturiste/rabbitmq-consumer`

## Require

- PHP >= 7.1

## Usage

In your PHP deamon script:

```
require 'vendor/autoload.php';

$connection = new \PhpAmqpLib\Connection\AMQPStreamConnection("localhost", 5672, 'username', 'password', 'virtualhost');
$client = new \Lefuturiste\RabbitMQConsumer\Client($connection);
$client->addListener('my_queue', function(array $message, $rootValue){
    var_dump($message);
    echo "\n";
});
$client->listen();
```

## Tests

`phpunit tests`

## See also

- [RabbitMQ Publisher](https://github.com/lefuturiste/rabbitmq-publisher)
