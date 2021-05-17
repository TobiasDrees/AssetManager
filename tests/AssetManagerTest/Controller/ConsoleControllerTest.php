<?php

namespace AssetManagerTest\Controller;

use AssetManager\Command\Warmup;
use AssetManager\Resolver\MapResolver;
use AssetManager\Service\AssetCacheManager;
use AssetManager\Service\AssetFilterManager;
use AssetManager\Service\AssetManager;
use AssetManager\Service\MimeResolver;
use JSMin;
use PHPUnit\Framework\TestCase;
use Laminas\Router\RouteMatch;
use Laminas\Router\RouteMatch as V2RouteMatch;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Resolver\ResolverInterface;

class ConsoleControllerTest extends TestCase
{
    /**
     * @var Warmup
     */
    protected $command;
    protected $request;
    protected $routeMatch;
    protected $event;
    protected static $assetName;

    public static function setUpBeforeClass()
    {
        self::$assetName = '_assettest.' . time();
    }

    public function setUp()
    {
        require_once __DIR__ . '/../../_files/JSMin.inc';

        $config = array(
            'filters' => array(
                self::$assetName => array(
                    array(
                        'filter' => 'JSMin',
                    ),
                ),
            ),
        );

        $assetFilterManager = new AssetFilterManager($config['filters']);
        $assetCacheManager = $this->getAssetCacheManager();

        $resolver     = $this->getResolver();
        $assetManager = new AssetManager($resolver, $config);
        $assetManager->setAssetFilterManager($assetFilterManager);
        $assetManager->setAssetCacheManager($assetCacheManager);

        $this->routeMatch = $this->createRouteMatch(['controller' => 'console']);

        $this->event = new MvcEvent();
        $this->event->setRouteMatch($this->routeMatch);

        $this->command = new Warmup($assetManager, []);
    }

    public function createRouteMatch(array $params = [])
    {
        $class = class_exists(V2RouteMatch::class) ? V2RouteMatch::class : RouteMatch::class;
        return new $class($params);
    }

    /**
     *
     * @return ResolverInterface
     */
    protected function getResolver()
    {
        $mimeResolver = new MimeResolver();
        $resolver = new MapResolver(array(
            self::$assetName => __DIR__ . '/../../_files/require-jquery.js'
        ));
        $resolver->setMimeResolver($mimeResolver);
        return $resolver;
    }

    /**
     * @return AssetCacheManager
     */
    protected function getAssetCacheManager()
    {
        $serviceLocator = $this->createMock(ServiceLocatorInterface::class);
        $config = array(
            self::$assetName => array(
                'cache' => 'FilePathCache',
                'options' => array(
                    'dir' => sys_get_temp_dir()
                )
            ),
        );
        return new AssetCacheManager($serviceLocator, $config);
    }

    public function testWarmupAction()
    {
        $this->markTestSkipped('laminas-cli');
        $this->routeMatch->setParam('action', 'warmup');
        $this->command->dispatch($this->request);

        $dumpedAsset = sys_get_temp_dir() . '/' . self::$assetName;
        $this->assertEquals(
            file_get_contents($dumpedAsset),
            JSMin::minify(file_get_contents(__DIR__ . '/../../_files/require-jquery.js'))
        );
    }
}
