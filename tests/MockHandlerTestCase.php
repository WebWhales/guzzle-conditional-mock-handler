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
use PHPUnit\Framework\TestCase;
use WebWhales\GuzzleConditionalMockHandler\Handler as MockHandler;

/**
 * Class MockHandlerTestCase
 *
 * @package WebWhales\GuzzleConditionalMockHandler\Tests
 */
abstract class MockHandlerTestCase extends TestCase
{
    /**
     * @var \WebWhales\GuzzleConditionalMockHandler\Handler
     */
    private $mockHandler;

    /**
     * @param \GuzzleHttp\Client $client
     *
     * @return \WebWhales\GuzzleConditionalMockHandler\Handler
     */
    protected function getMockHandler(?Client &$client): MockHandler
    {
        $this->mockHandler = MockHandler::initializeWithClient($client);

        // Return the handler so a new response can be added to it
        return $this->mockHandler;
    }
}