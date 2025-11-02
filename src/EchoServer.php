<?php

declare(strict_types=1);

namespace App;

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket\InternetAddress;
use Psr\Log\NullLogger;

class EchoServer
{
    private ?SocketHttpServer $server = null;
    private bool $running = false;

    public function __construct(
        private readonly string $host = '127.0.0.1',
        private readonly int $port = 8080
    ) {
    }

    public function start(): void
    {
        if ($this->running) {
            throw new \RuntimeException('Server is already running');
        }

        $this->server = SocketHttpServer::createForDirectAccess(
            new NullLogger()
        );

        $this->server->expose(new InternetAddress($this->host, $this->port));

        $errorHandler = new DefaultErrorHandler();

        $this->server->start(
            new ClosureRequestHandler(function (Request $request) {
                return $this->handleRequest($request);
            }),
            $errorHandler
        );

        $this->running = true;

        echo "HTTP Echo server listening on http://{$this->host}:{$this->port}\n";
    }

    private function handleRequest(Request $request): Response
    {
        $method = $request->getMethod();
        $uri = $request->getUri();
        $headers = $request->getHeaders();
        $body = $request->getBody()->buffer();

        // Build echo response with request details
        $responseData = [
            'method' => $method,
            'uri' => (string) $uri,
            'headers' => $headers,
            'body' => $body,
        ];

        // Log request
        echo sprintf(
            "[%s] %s %s - Body: %d bytes\n",
            date('Y-m-d H:i:s'),
            $method,
            $uri,
            strlen($body)
        );

        // Return JSON response with echoed data
        return new Response(
            status: 200,
            headers: ['content-type' => 'application/json'],
            body: json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    public function stop(): void
    {
        if ($this->server) {
            $this->server->stop();
            $this->running = false;
            echo "Server stopped\n";
        }
    }

    public function isRunning(): bool
    {
        return $this->running;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getServer(): ?SocketHttpServer
    {
        return $this->server;
    }
}