# Conditional Mock Handler for Guzzle

This is a package that offers a way to load mock responses conditionally based on the URL, instead of a fixed queue.


## Installation

Install this package using composer:

```
composer require --dev webwhales/guzzle-conditional-mock-handler
```


## Simple Example

To use this Conditional Mock Handler, use the following example:

```php
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use WebWhales\GuzzleConditionalMockHandler\Handler as MockHandler;

$mockHandler = new MockHandler();

// Create a handler stack to mock the Guzzle Client with
$handler = HandlerStack::create($mockHandler);
$client  = new Client(['handler' => $handler]);

// Add mocked responses
$mockHandler->addResponse('https://example.com', new Response(200, [], 'This is a test'));


// Make a request to a matching URL
$response = $client->request('GET', 'https://example.com');

echo $response->getBody();
// Outputs "This is a test"


// Make a request to a non matching URL
$response = $client->request('GET', 'https://www.example.com');

echo $response->getBody();
// Outputs the actual content of https://www.example.com
```


## Regex example

This packages also supports regex patterns:

```php
// Add mocked responses
$mockHandler->addResponse('^http(s)?://example\.', new Response(200, [], 'This is a test'));


// Make a request to a matching URL
$response = $client->request('GET', 'https://example.com');

echo $response->getBody();
// Outputs "This is a test"


// Make a request to a non matching URL
$response = $client->request('GET', 'https://www.example.com');

echo $response->getBody();
// Outputs the actual content of https://www.example.com
```


## Initialization Helper

Using this handler can also be simplified using the the following helper:

```php
$client      = null;
$mockHandler = MockHandler::initializeWithClient($client);
```


## License

The this package is open source software licensed under the [MIT license](https://opensource.org/licenses/MIT)
