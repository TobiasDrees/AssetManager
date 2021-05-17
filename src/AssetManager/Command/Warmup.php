<?php

namespace AssetManager\Command;

use AssetManager\Service\AssetManager;
use Laminas\Cli\Command\AbstractParamAwareCommand;
use Laminas\Cli\Input\ParamAwareInputInterface;
use Laminas\Cli\Input\StringParam;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Warmup extends AbstractParamAwareCommand
{
    /**
     * @var \AssetManager\Service\AssetManager asset manager object
     */
    protected $assetManager;

    /**
     * @var array associative array represents app config
     */
    protected $appConfig;

    public function __construct(AssetManager $assetManager, array $appConfig)
    {
        parent::__construct();
        $this->assetManager = $assetManager;
        $this->appConfig    = $appConfig;
    }

    protected function configure()
    {
        $this->setName(self::$defaultName);
        $this->addParam(
            (new StringParam('purge'))
        );
        $this->addParam(
            (new StringParam('verbose'))
                ->setShortcut('v')
        );
    }

    /**
     * @param ParamAwareInputInterface $input
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $purge      = $input->hasParameterOption('purge');
        $verbose    = $input->hasParameterOption('verbose') || $input->hasParameterOption('v');

        // purge cache for every configuration
        if ($purge) {
            $this->purgeCache($output, $verbose);
        }

        $this->output('Collecting all assets...', $output, $verbose);

        $collection = $this->assetManager->getResolver()->collect();
        $this->output(sprintf('Collected %d assets, warming up...', count($collection)), $output, $verbose);

        foreach ($collection as $path) {
            $asset = $this->assetManager->getResolver()->resolve($path);
            $this->assetManager->getAssetFilterManager()->setFilters($path, $asset);
            $this->assetManager->getAssetCacheManager()->setCache($path, $asset)->dump();
        }

        $this->output('Warming up finished...', $output, $verbose);
        return AbstractParamAwareCommand::SUCCESS;
    }

    /**
     * Purges all directories defined as AssetManager cache dir.
     * @param bool $verbose verbose flag, default false
     * @return bool false if caching is not set, otherwise true
     */
    protected function purgeCache(OutputInterface $output, $verbose = false)
    {

        if (empty($this->appConfig['asset_manager']['caching'])) {
            return false;
        }

        foreach ($this->appConfig['asset_manager']['caching'] as $configName => $config) {

            if (empty($config['options']['dir'])) {
                continue;
            }
            $this->output(sprintf('Purging %s on "%s"...', $config['options']['dir'], $configName), $output, $verbose);

            $node = $config['options']['dir'];

            if ($configName !== 'default') {
                $node .= '/'.$configName;
            }

            $this->recursiveRemove($node, $output, $verbose);
        }

        return true;
    }

    /**
     * Removes given node from filesystem (recursively).
     * @param string $node - uri of node that should be removed from filesystem
     * @param bool $verbose verbose flag, default false
     */
    protected function recursiveRemove($node, OutputInterface $output, $verbose = false)
    {
        if (is_dir($node)) {
            $objects = scandir($node);

            foreach ($objects as $object) {
                if ($object === '.' || $object === '..') {
                    continue;
                }
                $this->recursiveRemove($node . '/' . $object, $output);
            }
        } elseif (is_file($node)) {
            $this->output(sprintf("unlinking %s...", $node), $output, $verbose);
            unlink($node);
        }
    }

    /**
     * Outputs given $line if $verbose i truthy value.
     * @param $line
     * @param bool $verbose verbose flag, default true
     */
    protected function output($line, OutputInterface $output, $verbose = true)
    {
        if ($verbose) {
            $output->writeln($line);
        }
    }
}
