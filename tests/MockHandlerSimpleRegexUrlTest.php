<?php
/**
 * Guzzle Conditional Mock Handler
 *
 * @author    Ronald Edelschaap (Web Whales) <ronald.edelschaap@webwhales.nl>
 * @copyright 2020 Web Whales (https://webwhales.nl)
 * @license   https://opensource.org/licenses/MIT MIT
 * @link      https://github.com/WebWhales/guzzle-conditional-mock-handler
 */

namespace WebWhales\GuzzleConditionalMockHandler\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

/**
 * Class MockHandlerRegexUrlTest
 *
 * @package WebWhales\GuzzleConditionalMockHandler\Tests
 */
class MockHandlerSimpleRegexUrlTest extends MockHandlerTestCase
{
    /**
     * @test
     */
    public function Should_UseMockResponse_When_UrlMatchesSimpleRegexPattern()
    {
        /**
         * Prepare the test
         *
         * @var \GuzzleHttp\Client $client
         */
        $client      = null;
        $mockHandler = $this->getMockHandler($client);


        // Add a mocked response
        $mockHandler->addResponse('~httpbin\.org~', new Response(200, [], 'This is a test'));


        /*
         * Run the tests
         */
        foreach ([
            'https://httpbin.org/get',
            'http://httpbin.org/get',
            'https://www.httpbin.org/get',
            'https://www.HTTPBIN.org/get',
        ] as $url) {
            // Execute the request
            $response = $client->get($url);

            // Assert the results
            $this->assertEquals('This is a test', (string) $response->getBody(),
                "Response body does not match the mocked response body for [{$url}]");
        }
    }

    /**
     * @test
     */
    public function ShouldNot_UseMockResponse_When_UrlDoesNotMatchSimpleRegexPattern()
    {
        /*
         * Prepare the test
         */
        $client      = new Client(['http_errors' => false]);
        $mockHandler = $this->getMockHandler($client);


        // Add a mocked response
        $mockHandler->addResponse('~https://httpbin\.org~', new Response(200, [], 'This is a test'));


        /*
         * Run the tests
         */
        foreach ([
            'http://httpbin.org/get',
            'https://www.httpbin.org/get',
        ] as $url) {
            // Execute the request
            $response = $client->get($url);

            // Assert the results
            $this->assertNotEquals('This is a test', (string) $response->getBody(),
                "Response body matches the mocked response body for [{$url}]");
        }
    }
}