<?php

use Fetch\Http;
use Fetch\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

beforeEach(function () {
    // Setup a mock Guzzle client to use in each test
    $this->mock = new MockHandler();
    $this->handler = HandlerStack::create($this->mock);
    $this->client = new Client(['handler' => $this->handler]);
});

afterEach(function () {
    // Reset the mock handler after each test
    $this->mock->reset();
});

test('fetch can handle a successful GET request', function () {
    // Simulate a Guzzle success response
    $this->mock->append(new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"message":"success"}'));

    $response = fetch('/', [
        'client' => $this->client,
        'method' => 'GET',
        'headers' => ['Accept' => 'application/json']
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->json())->toMatchArray(['message' => 'success']);
});

test('fetch can handle a 404 error response', function () {
    $this->mock->append(new GuzzleResponse(404, [], 'Not Found'));

    $response = fetch('/', [
        'client' => $this->client,
        'method' => 'GET'
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(404);
    expect($response->text())->toBe('Not Found');
});

test('fetch_async can handle a successful GET request', function () {
    $this->mock->append(new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"message":"success"}'));

    $promise = fetch_async('/', [
        'client' => $this->client,
        'method' => 'GET',
        'headers' => ['Accept' => 'application/json']
    ]);
    $response = $promise->wait();

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->json())->toMatchArray(['message' => 'success']);
});

// Test for asynchronous fetch with a 500 error
test('fetch_async can handle a 500 error response', function () {
    $this->mock->append(new GuzzleResponse(500, [], 'Internal Server Error'));

    $promise = fetch_async('/', [
        'client' => $this->client,
        'method' => 'GET'
    ]);
    $response = $promise->wait();

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(500);
    expect($response->text())->toBe('Internal Server Error');
});

test('fetch can send multipart form data', function () {
    $this->mock->append(new GuzzleResponse(200, [], 'OK'));

    $response = fetch('/upload', [
        'client' => $this->client,
        'method' => 'POST',
        'multipart' => [
            [
                'name'     => 'file',
                'contents' => 'file content',
                'filename' => 'test.txt'
            ]
        ]
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->text())->toBe('OK');
});

test('fetch can send JSON data', function () {
    $this->mock->append(new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"success":true}'));

    $response = fetch('/api', [
        'client' => $this->client,
        'method' => 'POST',
        'json' => [
            'key' => 'value'
        ]
    ]);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->json())->toMatchArray(['success' => true]);
});

test('fetch uses a singleton instance of Guzzle client', function () {
    // Simulate Guzzle success responses
    $this->mock->append(
        new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"message":"success"}'),
        new GuzzleResponse(200, ['Content-Type' => 'application/json'], '{"message":"success"}')
    );

    // First fetch call
    $response1 = fetch('/', [
        'client' => $this->client,
        'method' => 'GET',
        'headers' => ['Accept' => 'application/json']
    ]);

    // Second fetch call
    $response2 = fetch('/', [
        'client' => $this->client,
        'method' => 'GET',
        'headers' => ['Accept' => 'application/json']
    ]);

    // Check if both responses are instances of Response class
    expect($response1)->toBeInstanceOf(Response::class);
    expect($response2)->toBeInstanceOf(Response::class);

    // Check if both responses have the same status code and body
    expect($response1->getStatusCode())->toBe(200);
    expect($response1->json())->toMatchArray(['message' => 'success']);
    expect($response2->getStatusCode())->toBe(200);
    expect($response2->json())->toMatchArray(['message' => 'success']);

    // Check if the Guzzle client instance is the same for both fetch calls
    expect($this->client)->toBe(Http::getClient());
});
