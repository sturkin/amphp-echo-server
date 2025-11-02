#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\EchoServer;
use Revolt\EventLoop;

$host = $argv[1] ?? '127.0.0.1';
$port = (int) ($argv[2] ?? 8080);

$server = new EchoServer($host, $port);

// Handle shutdown signals
$signalHandler = function (string $watcherId, int $signo) use ($server): void {
    echo "\nShutting down...\n";
    $server->stop();
    EventLoop::getDriver()->stop();
};

EventLoop::onSignal(SIGINT, $signalHandler);
EventLoop::onSignal(SIGTERM, $signalHandler);

try {
    $server->start();

    echo "Press Ctrl+C to stop the server\n";

    // Keep the event loop running
    EventLoop::run();
} catch (\Throwable $e) {
    echo "Error: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
