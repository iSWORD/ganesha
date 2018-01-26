<?php
namespace Ackintosh\Ganesha;

use Ackintosh\Ganesha\Storage\Adapter\Redis;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

class GuzzleMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Cleanup test statistics before run tests
        $r = new \Redis();
        $r->connect(
            getenv('GANESHA_EXAMPLE_REDIS') ? getenv('GANESHA_EXAMPLE_REDIS') : 'localhost'
        );
        $r->flushAll();
    }

    /**
     * @test
     * @vcr responses.yml
     */
    public function recordsSuccessOn200()
    {
        $redis = new \Redis();
        $redis->connect(
            getenv('GANESHA_EXAMPLE_REDIS') ? getenv('GANESHA_EXAMPLE_REDIS') : 'localhost'
        );
        $adapter = new Redis($redis);
        $ganesha = Builder::build([
            'timeWindow'            => 30,
            'failureRateThreshold'  => 50,
            'minimumRequests'       => 10,
            'intervalToHalfOpen'    => 5,
            'adapter'               => $adapter,
        ]);

        $middleware = new GuzzleMiddleware($ganesha);
        $handlers = HandlerStack::create();
        $handlers->push($middleware);
        $client = new Client([
            'handler' => $handlers,
        ]);

        $response = $client->get('http://api.example.com/awesome_resource');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            1,
            $adapter->load(Storage::KEY_PREFIX . 'api.example.com' . Storage::KEY_SUFFIX_SUCCESS)
        );
    }
}