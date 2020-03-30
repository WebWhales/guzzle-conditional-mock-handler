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
 * Class MockHandlerSimpleUrlTest
 *
 * @package WebWhales\GuzzleConditionalMockHandler\Tests
 */
class MockHandlerSimpleUrlTest extends MockHandlerTestCase
{
    /**
     * @test
     */
    public function Should_UseMockResponse_When_UrlMatchesSimpleUrl()
    {
        /*
         * Prepare the test
         */
        $client      = null;
        $mockHandler = $this->getMockHandler($client);


        // Add a mocked response
        $mockHandler->addResponse('https://example.com', new Response(200, [], 'This is a test'));


        /*
         * Execute the request
         */
        $response = $client->get('https://example.com');


        /*
         * Assert the results
         */
        $this->assertEquals('This is a test', (string) $response->getBody(),
            'Response body does not match the mocked response body');
    }

    /**
     * @test
     */
    public function ShouldNot_UseMockResponse_When_UrlDoesNotMatchSimpleUrl()
    {
        /*
         * Prepare the test
         */
        $client      = new Client(['http_errors' => false]);
        $mockHandler = $this->getMockHandler($client);


        // Add a mocked response
        $mockHandler->addResponse('https://example.com', new Response(200, [], 'This is a test'));


        /*
         * Execute the request
         */
        $response = $client->get('https://example2.com');


        /*
         * Assert the results
         */
        $this->assertNotEquals('This is a test', (string) $response->getBody(),
            'Response body does match the mocked response body');
    }
}