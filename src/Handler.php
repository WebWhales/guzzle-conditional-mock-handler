<?php
/**
 * Guzzle Conditional Mock Handler
 *
 * @author    Ronald Edelschaap (Web Whales) <ronald.edelschaap@webwhales.nl>
 * @copyright 2020 Web Whales (https://webwhales.nl)
 * @license   https://opensource.org/licenses/MIT MIT
 * @link      https://github.com/WebWhales/guzzle-conditional-mock-handler
 */

namespace WebWhales\GuzzleConditionalMockHandler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class Handler
 *
 * @package WebWhales\GuzzleConditionalMockHandler
 */
class Handler
{
    /**
     * @var ResponseInterface[]
     */
    private $responsesByRegex = [];

    /**
     * @var ResponseInterface[]
     */
    private $responsesByUrl = [];

    /**
     * @var ResponseInterface[]
     */
    private $responseCache = [];

    /**
     * @param \Psr\Http\Message\RequestInterface $request
     * @param array                              $options
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        // When there is a mocked response for this URL, return it
        if ($response = $this->getResponseForUrl($request->getUri()->__toString())) {
            return $this->handleResponse($request, $response, $options);
        }

        // Otherwise, pass the request to the outside world using a fresh Client instance with a regular handler
        $options = \array_diff_key($options, \array_flip(['handler']));
        $client  = new Client();

        return $client->sendAsync($request, $options);
    }

    /**
     * @param string                              $url      Can be a absolute URL or a regex pattern
     * @param \Psr\Http\Message\ResponseInterface $response The response to use
     *
     * @return \WebWhales\GuzzleConditionalMockHandler\Handler
     */
    public function addResponse(string $url, ResponseInterface $response): self
    {
        // Put the response in the correct bag
        if (@preg_match($url, null) !== false) {
            $this->responsesByRegex[$url] = $response;
        } else {
            $this->responsesByUrl[$url] = $response;
        }

        return $this;
    }

    /**
     * Clear responses by URL
     *
     * @param string $url
     *
     * @return \WebWhales\GuzzleConditionalMockHandler\Handler
     */
    public function removeResponse(string $url): self
    {
        unset(
            $this->responsesByRegex[$url],
            $this->responsesByUrl[$url],
            $this->responseCache[$url]
        );

        return $this;
    }

    /**
     * Clear all responses
     *
     * @return \WebWhales\GuzzleConditionalMockHandler\Handler
     */
    public function resetResponses(): self
    {
        $this->responsesByRegex = $this->responsesByUrl = $this->responseCache = [];

        return $this;
    }

    /**
     * Get a registered response for a URL, where $url may be a specific URL or a regex pattern
     *
     * @param string $url
     *
     * @return \Psr\Http\Message\ResponseInterface|null
     */
    private function getResponseForUrl(string $url): ?ResponseInterface
    {
        // First, see if we already have seen this url
        if (isset($this->responseCache[$url])) {
            return $this->responseCache[$url];
        }

        // Second, try to get the response by just the URL
        if (isset($this->responsesByUrl[$url])) {
            return $this->responseCache[$url] = $this->responsesByUrl[$url];
        }

        // Otherwise, try the regex patterns
        foreach ($this->responsesByRegex as $regex => $response) {
            if (\preg_match($regex, $url)) {
                return $this->responseCache[$url] = $response;
            }
        }

        return null;
    }

    /**
     * @param \Psr\Http\Message\RequestInterface  $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array                               $options
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    private function handleResponse(
        RequestInterface $request,
        ResponseInterface $response,
        array $options
    ): PromiseInterface {
        if (isset($options['delay']) && \is_numeric($options['delay'])) {
            \usleep($options['delay'] * 1000);
        }

        if (isset($options['on_headers'])) {
            if (! \is_callable($options['on_headers'])) {
                throw new \InvalidArgumentException('on_headers must be callable');
            }
            try {
                $options['on_headers']($response);
            } catch (\Exception $e) {
                $msg      = 'An error was encountered during the on_headers event';
                $response = new RequestException($msg, $request, $response, $e);
            }
        }

        if (\is_callable($response)) {
            $response = \call_user_func($response, $request, $options);
        }

        $response = $response instanceof \Throwable
            ? \GuzzleHttp\Promise\rejection_for($response)
            : \GuzzleHttp\Promise\promise_for($response);

        return $response->then(
            function (?ResponseInterface $value) use ($request, $options) {
                $this->invokeStats($request, $options, $value);

                if ($value !== null && isset($options['sink'])) {
                    $contents = (string) $value->getBody();
                    $sink     = $options['sink'];

                    if (\is_resource($sink)) {
                        \fwrite($sink, $contents);
                    } elseif (\is_string($sink)) {
                        \file_put_contents($sink, $contents);
                    } elseif ($sink instanceof StreamInterface) {
                        $sink->write($contents);
                    }
                }

                return $value;
            },
            function ($reason) use ($request, $options) {
                $this->invokeStats($request, $options, null, $reason);

                return \GuzzleHttp\Promise\rejection_for($reason);
            }
        );
    }

    /**
     * @param \Psr\Http\Message\RequestInterface       $request
     * @param array                                    $options
     * @param \Psr\Http\Message\ResponseInterface|null $response
     * @param null                                     $reason
     */
    private function invokeStats(
        RequestInterface $request,
        array $options,
        ResponseInterface $response = null,
        $reason = null
    ): void {
        if (isset($options['on_stats'])) {
            $transferTime = isset($options['transfer_time']) ? $options['transfer_time'] : 0;
            $stats        = new TransferStats($request, $response, $transferTime, $reason);

            \call_user_func($options['on_stats'], $stats);
        }
    }

    /**
     * Initialize a Mock Handler for a Guzzle Client
     *
     * @param \GuzzleHttp\Client|null $client
     * @param array                   $config
     *
     * @return \WebWhales\GuzzleConditionalMockHandler\Handler
     */
    public static function initializeWithClient(?Client &$client, array $config = []): self
    {
        $mockHandler = new self;

        // Create a handler stack to mock the Guzzle Client with
        $handler = HandlerStack::create($mockHandler);
        $client  = new Client(['handler' => $handler] + ($client ? $client->getConfig() : []) + $config);

        return $mockHandler;
    }
}