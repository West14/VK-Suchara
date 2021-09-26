<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 08.08.2021
 * Time: 20:48
 * Made with <3 by West from Bubuni Team
 */

namespace App\Logger;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMq extends AbstractLogger
{
    protected AMQPChannel $channel;

    public function setup(): bool
    {
        /** @var AMQPStreamConnection $connection */
        $connection = \App::app()->container()['rabbitmq'];

        $channel = $this->channel = $connection->channel();
        $channel->exchange_declare($_ENV['RABBITMQ_EXCHANGE_NAME'], 'fanout', durable: true, auto_delete: false);

        return $connection->isConnected();
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $logMessage = new AMQPMessage(json_encode([
            'level' => $level,
            'message' => $message
        ]));

        $this->channel->basic_publish($logMessage, $_ENV['RABBITMQ_EXCHANGE_NAME']);
    }

    public function __destruct()
    {
        $this->channel->close();
    }
}