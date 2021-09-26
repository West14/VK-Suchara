<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 07.08.2021
 * Time: 13:51
 * Made with <3 by West from Bubuni Team
 */

namespace App\Command;

use App;
use PDO;
use VK\Client\VKApiClient;


abstract class AbstractCommand
{
    public function __construct(
        private App $app,
        protected array $message,
        protected array $params
    ) {}

    abstract public function handle(): void;

    public function canUse(int $userId): bool
    {
        return true;
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    protected function sendReply(string $text, array $params = []): void
    {
        $this->vkApi()->messages()->send($_ENV['VK_ACCESS_KEY'], array_merge([
            'peer_id' => $this->message['peer_id'],
            'message' => $text,
            'random_id' => rand(1, 1000000)
        ], $params));
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    protected function setTyping(): void
    {
        $this->vkApi()->messages()->setActivity($_ENV['VK_ACCESS_KEY'],
        [
            'peer_id' => $this->message['peer_id'],
            'type' => 'typing',
            'user_id' => $_ENV['VK_GROUP_ID'],
        ]);
        sleep(5);
    }

    /**
     * @throws \VK\Exceptions\VKApiException
     * @throws \VK\Exceptions\VKClientException
     */
    protected function getUserNameById(int $userId): string
    {
        $user = $this->vkApi()->users()->get($_ENV['VK_ACCESS_KEY'],
        [
            'user_ids' => $userId
        ])[0];

        return "{$user['first_name']} {$user['last_name']}";
    }

    protected function isDeveloper(): bool
    {
        return $this->message['from_id'] == $_ENV['VK_DEVELOPER_ID'];
    }

    protected function formatUserTag(int $userId, string $name): string
    {
        return "[id{$userId}|{$name}]";
    }

    protected function vkApi(): VKApiClient
    {
        return \App::app()->vkApi();
    }

    protected function app(): App
    {
        return $this->app;
    }

    protected function db(): PDO
    {
        return $this->app()->container()['db'];
    }
}