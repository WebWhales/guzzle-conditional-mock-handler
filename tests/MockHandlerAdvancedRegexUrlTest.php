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
 * Class MockHandlerAdvancedRegexUrlTest
 *
 * @package WebWhales\GuzzleConditionalMockHandler\Tests
 */
class MockHandlerAdvancedRegexUrlTest extends MockHandlerTestCase
{
    /**
     * @test
     */
    public function Should_UseMockResponse_When_UrlMatchesAdvancedRegexPattern()
    {
        /*
         * Prepare the test
         */
        $client      = null;
        $mockHandler = $this->getMockHandler($client);


        // Add mocked responses
        $mockHandler->addResponse('~^https://httpbin\.org/p(ost|ut)~', new Response(200, [], 'Test scenario 1'));
        $mockHandler->addResponse('~http(s)?://httpbin\.org~', new Response(200, [], 'Test scenario 2'));
        $mockHandler->addResponse('~httpbin\.org/get$~', new Response(200, [], 'Test scenario 3'));
        $mockHandler->addResponse('~httpbin\.org/p(ost|ut)$~', new Response(200, [], 'Test scenario 4'));


        /*
         * Run the tests
         */
        foreach ([
            'https://httpbin.org/get'     => 'Test scenario 2',
            'http://httpbin.org/get'      => 'Test scenario 2',
            'https://www.httpbin.org/get' => 'Test scenario 3',
        ] as $url => $expectedBody) {
            // Execute the request
            $response = $client->get($url);

            // Assert the results
            $this->assertEquals($expectedBody, (string) $response->getBody(),
                "Response body does not match the mocked response body for [{$url}]");
        }

        foreach ([
            'https://httpbin.org/post'    => 'Test scenario 1',
            'https://www.httpbin.org/put' => 'Test scenario 4',
        ] as $url => $expectedBody) {
            // Execute the request
            $response = $client->request(\strpos($url, 'post') ? 'post' : 'put', $url);

            // Assert the results
            $this->assertEquals($expectedBody, (string) $response->getBody(),
                "Response body does not match the mocked response body for [{$url}]");
        }
    }

    /**
     * @test
     */
    public function ShouldNot_UseMockResponse_When_UrlDoesNotMatchAdvancedRegexPattern()
    {
        /*
         * Prepare the test
         */
        $client      = new Client(['http_errors' => false]);
        $mockHandler = $this->getMockHandler($client);


        // Add mocked responses
        $mockHandler->addResponse('~^https://httpbin\.org/p(ost|ut)~', new Response(200, [], 'This is a test'));
        $mockHandler->addResponse('~http(s)?://httpbin\.org~', new Response(200, [], 'This is a test'));
        $mockHandler->addResponse('~httpbin\.org/get$~', new Response(200, [], 'This is a test'));
        $mockHandler->addResponse('~httpbin\.org/p(ost|ut)$~', new Response(200, [], 'This is a test'));


        /*
         * Run the tests
         */
        foreach ([
            'https://www.httpbin.org/get?test=1',
            'https://www.httpbin.org/anything',
        ] as $url) {
            // Execute the request
            $response = $client->get($url);

            // Assert the results
            $this->assertNotEquals('This is a test', (string) $response->getBody(),
                "Response body matches the mocked response body for [{$url}]");
        }

        foreach ([
            'https://www.httpbin.org/anything/post',
            'https://www.httpbin.org/put?test=1',
        ] as $url) {
            // Execute the request
            $response = $client->request(\strpos($url, 'post') ? 'post' : 'put', $url);

            // Assert the results
            $this->assertNotEquals('This is a test', (string) $response->getBody(),
                "Response body matches the mocked response body for [{$url}]");
        }
    }
}