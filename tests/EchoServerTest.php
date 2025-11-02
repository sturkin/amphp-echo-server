<?php

declare(strict_types=1);

namespace Tests;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use App\EchoServer;
use PHPUnit\Framework\TestCase;
use function Amp\async;
use function Amp\delay;

class EchoServerTest extends TestCase
{
    private ?EchoServer $server = null;
    private int $testPort = 9999;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testPort = random_int(10000, 60000);
    }

    protected function tearDown(): void
    {
        if ($this->server && $this->server->isRunning()) {
            $this->server->stop();
        }
        parent::tearDown();
    }

    public function testServerConfiguration(): void
    {
        $server = new EchoServer('localhost', $this->testPort);

        $this->assertSame('localhost', $server->getHost());
        $this->assertSame($this->testPort, $server->getPort());
        $this->assertFalse($server->isRunning());
    }

    public function testServerStartsAndAcceptsConnections(): void
    {
        $this->server = new EchoServer('127.0.0.1', $this->testPort);
        $this->server->start();

        delay(0.1);

        $this->assertTrue($this->server->isRunning());

        $client = HttpClientBuilder::buildDefault();
        $request = new Request("http://127.0.0.1:{$this->testPort}/test");
        $response = $client->request($request);

        $this->assertSame(200, $response->getStatus());

        $this->server->stop();
    }

    public function testEchoFunctionality(): void
    {
        $this->server = new EchoServer('127.0.0.1', $this->testPort);
        $this->server->start();

        delay(0.1);

        $client = HttpClientBuilder::buildDefault();
        $testBody = "Hello, Echo Server!";
        $request = new Request("http://127.0.0.1:{$this->testPort}/test", 'POST');
        $request->setBody($testBody);

        $response = $client->request($request);
        $responseBody = $response->getBody()->buffer();
        $data = json_decode($responseBody, true);

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('POST', $data['method']);
        $this->assertSame($testBody, $data['body']);
        $this->assertStringContainsString('/test', $data['uri']);

        $this->server->stop();
    }

    public function testGetRequest(): void
    {
        $this->server = new EchoServer('127.0.0.1', $this->testPort);
        $this->server->start();

        delay(0.1);

        $client = HttpClientBuilder::buildDefault();
        $request = new Request("http://127.0.0.1:{$this->testPort}/api/users?id=123");

        $response = $client->request($request);
        $responseBody = $response->getBody()->buffer();
        $data = json_decode($responseBody, true);

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('GET', $data['method']);
        $this->assertStringContainsString('/api/users', $data['uri']);
        $this->assertStringContainsString('id=123', $data['uri']);

        $this->server->stop();
    }

    public function testMultipleRequests(): void
    {
        $this->server = new EchoServer('127.0.0.1', $this->testPort);
        $this->server->start();

        delay(0.1);

        $client = HttpClientBuilder::buildDefault();

        $testData = [
            ['method' => 'GET', 'path' => '/api/test1'],
            ['method' => 'POST', 'path' => '/api/test2', 'body' => 'test body 1'],
            ['method' => 'PUT', 'path' => '/api/test3', 'body' => 'test body 2'],
        ];

        foreach ($testData as $test) {
            $request = new Request(
                "http://127.0.0.1:{$this->testPort}{$test['path']}",
                $test['method']
            );

            if (isset($test['body'])) {
                $request->setBody($test['body']);
            }

            $response = $client->request($request);
            $responseBody = $response->getBody()->buffer();
            $data = json_decode($responseBody, true);

            $this->assertSame(200, $response->getStatus());
            $this->assertSame($test['method'], $data['method']);
            $this->assertStringContainsString($test['path'], $data['uri']);

            if (isset($test['body'])) {
                $this->assertSame($test['body'], $data['body']);
            }
        }

        $this->server->stop();
    }

    public function testConcurrentRequests(): void
    {
        $this->server = new EchoServer('127.0.0.1', $this->testPort);
        $this->server->start();

        delay(0.1);

        $client = HttpClientBuilder::buildDefault();
        $futures = [];

        // Send 5 concurrent requests
        for ($i = 0; $i < 5; $i++) {
            $futures[] = async(function () use ($client, $i) {
                $request = new Request(
                    "http://127.0.0.1:{$this->testPort}/concurrent",
                    'POST'
                );
                $request->setBody("Request {$i}");

                $response = $client->request($request);
                $responseBody = $response->getBody()->buffer();
                $data = json_decode($responseBody, true);

                return [
                    'status' => $response->getStatus(),
                    'body' => $data['body'],
                ];
            });
        }

        // Wait for all requests to complete
        foreach ($futures as $i => $future) {
            $result = $future->await();
            $this->assertSame(200, $result['status']);
            $this->assertSame("Request {$i}", $result['body']);
        }

        $this->server->stop();
    }

    public function testServerThrowsExceptionWhenAlreadyRunning(): void
    {
        $this->server = new EchoServer('127.0.0.1', $this->testPort);
        $this->server->start();

        delay(0.1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Server is already running');

        $this->server->start();
    }
}