<?php

namespace App\Config;

class MqttBroker
{
    private $host = '127.0.0.1';
    private $port = 1883;
    private $socket;
    private array $clients = [];
    private array $topics = [];
    private bool $running = false;
    private array $messages = [];

    public function __construct(string $host = '127.0.0.1', int $port = 1883)
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function start(): bool
    {
        $this->socket = @stream_socket_server("tcp://{$this->host}:{$this->port}", $errno, $errstr);
        if (!$this->socket) {
            return false;
        }
        stream_set_blocking($this->socket, false);
        $this->running = true;
        return true;
    }

    public function stop(): void
    {
        $this->running = false;
        if ($this->socket) {
            fclose($this->socket);
        }
        foreach ($this->clients as $client) {
            @fclose($client);
        }
    }

    public function isRunning(): bool
    {
        return $this->running;
    }

    public function getTopicCount(): int
    {
        return count($this->topics);
    }

    public function getClientCount(): int
    {
        return count($this->clients);
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getTopics(): array
    {
        return array_keys($this->topics);
    }

    public function getStatus(): string
    {
        return $this->running ? 'Running' : 'Stopped';
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getHost(): string
    {
        return $this->host;
    }
}