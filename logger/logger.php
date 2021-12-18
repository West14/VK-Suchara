<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 08.08.2021
 * Time: 22:09
 * Made with <3 by West from Bubuni Team
 */

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection(
    $_ENV['RABBITMQ_HOST'],
    $_ENV['RABBITMQ_PORT'],
    $_ENV['RABBITMQ_USER'],
    $_ENV['RABBITMQ_PASSWORD']
);

$vkApi = new \VK\Client\VKApiClient();

$exchangeName = $_ENV['RABBITMQ_EXCHANGE_NAME'];

$channel = $connection->channel();
$channel->exchange_declare($exchangeName, 'fanout', durable: true, auto_delete: false);

list($queueName, ,) = $channel->queue_declare(exclusive: true, auto_delete: false);
$channel->queue_bind($queueName, $exchangeName);

$callback = function (AMQPMessage $message) use ($vkApi)
{
    $log = @json_decode($message->body, true);
    $msgFormatted = "[{$log['level']}] {$log['message']}";

    echo "$msgFormatted\n";

    try {
        $vkApi->messages()->send($_ENV['VK_ACCESS_KEY'], [
            'peer_id' => $_ENV['VK_DEVELOPER_ID'],
            'random_id' => rand(1, 10000),
            'message' => $msgFormatted
        ]);
    } catch (\VK\Exceptions\VKApiException $e)
    {
        echo "Exception while sending log to VK: {$e->getErrorCode()} {$e->getErrorMessage()}";
        return;
    }

    $message->ack();
};

$channel->basic_consume($queueName, no_ack: false, callback: $callback);
while ($channel->is_open()) {
    try {
        $channel->wait();
    } catch (ErrorException $e)
    {
        echo 'Error while processing message: ' . $e->getMessage();
    }
}

$channel->close();

try {
    $connection->close();
}
catch (Exception $e) {
    echo 'Error while closing connection: ' . $e->getMessage();
}