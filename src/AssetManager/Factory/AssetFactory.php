<?php

namespace AssetManager\Factory;

use AssetManager\Asset\AssetCollection;
use AssetManager\Asset\AssetCollectionInterface;
use AssetManager\Asset\AssetInterface;
use AssetManager\Asset\AssetReference;
use AssetManager\Asset\FileAsset;
use AssetManager\Asset\GlobAsset;
use AssetManager\Asset\HttpAsset;
use AssetManager\AssetManager;
use AssetManager\Factory\Worker\WorkerInterface;
use AssetManager\Filter\DependencyExtractorInterface;
use AssetManager\FilterManager;

/**
 * The asset factory creates asset objects.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class AssetFactory
{
    private $root;
    private $debug;
    private $output;
    private $workers;
    private $am;
    private $fm;

    /**
     * Constructor.
     *
     * @param string  $root  The default root directory
     * @param Boolean $debug Filters prefixed with a "?" will be omitted in debug mode
     */
    public function __construct($root, $debug = false)
    {
        $this->root      = rtrim($root, '/');
        $this->debug     = $debug;
        $this->output    = 'assetic/*';
        $this->workers   = [];
    }

    /**
     * Sets debug mode for the current factory.
     *
     * @param Boolean $debug Debug mode
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * Checks if the factory is in debug mode.
     *
     * @return Boolean Debug mode
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * Sets the default output string.
     *
     * @param string $output The default output string
     */
    public function setDefaultOutput($output)
    {
        $this->output = $output;
    }

    /**
     * Adds a factory worker.
     *
     * @param WorkerInterface $worker A worker
     */
    public function addWorker(WorkerInterface $worker)
    {
        $this->workers[] = $worker;
    }

    /**
     * Returns the current asset manager.
     *
     * @return AssetManager|null The asset manager
     */
    public function getAssetManager()
    {
        return $this->am;
    }

    /**
     * Sets the asset manager to use when creating asset references.
     *
     * @param AssetManager $am The asset manager
     */
    public function setAssetManager(AssetManager $am)
    {
        $this->am = $am;
    }

    /**
     * Returns the current filter manager.
     *
     * @return FilterManager|null The filter manager
     */
    public function getFilterManager()
    {
        return $this->fm;
    }

    /**
     * Sets the filter manager to use when adding filters.
     *
     * @param FilterManager $fm The filter manager
     */
    public function setFilterManager(FilterManager $fm)
    {
        $this->fm = $fm;
    }

    /**
     * Creates a new asset.
     *
     * Prefixing a filter name with a question mark will cause it to be
     * omitted when the factory is in debug mode.
     *
     * Available options:
     *
     *  * output: An output string
     *  * name:   An asset name for interpolation in output patterns
     *  * debug:  Forces debug mode on or off for this asset
     *  * root:   An array or string of more root directories
     *
     * @param array|string $inputs  An array of input strings
     * @param array|string $filters An array of filter names
     * @param array        $options An array of options
     *
     * @return AssetCollection An asset collection
     */
    public function createAsset($inputs = [], $filters = [], array $options = [])
    {
        if (!is_array($inputs)) {
            $inputs = array($inputs);
        }

        if (!is_array($filters)) {
            $filters = array($filters);
        }

        if (!isset($options['output'])) {
            $options['output'] = $this->output;
        }

        if (!isset($options['vars'])) {
            $options['vars'] = [];
        }

        if (!isset($options['debug'])) {
            $options['debug'] = $this->debug;
        }

        if (!isset($options['root'])) {
            $options['root'] = array($this->root);
        } else {
            if (!is_array($options['root'])) {
                $options['root'] = array($options['root']);
            }

            $options['root'][] = $this->root;
        }

        if (!isset($options['name'])) {
            $options['name'] = $this->generateAssetName($inputs, $filters, $options);
        }

        $asset = $this->createAssetCollection([], $options);
        $extensions = [];

        // inner assets
        foreach ($inputs as $input) {
            if (is_array($input)) {
                // nested formula
                $asset->add(call_user_func_array(array($this, 'createAsset'), $input));
            } else {
                $asset->add($this->parseInput($input, $options));
                $extensions[pathinfo($input, PATHINFO_EXTENSION)] = true;
            }
        }

        // filters
        foreach ($filters as $filter) {
            if ('?' != $filter[0]) {
                $asset->ensureFilter($this->getFilter($filter));
            } elseif (!$options['debug']) {
                $asset->ensureFilter($this->getFilter(substr($filter, 1)));
            }
        }

        // append variables
        if (!empty($options['vars'])) {
            $toAdd = [];
            foreach ($options['vars'] as $var) {
                if (false !== strpos($options['output'], '{'.$var.'}')) {
                    continue;
                }

                $toAdd[] = '{'.$var.'}';
            }

            if ($toAdd) {
                $options['output'] = str_replace('*', '*.'.implode('.', $toAdd), $options['output']);
            }
        }

        // append consensus extension if missing
        if (1 == count($extensions) && !pathinfo($options['output'], PATHINFO_EXTENSION) && $extension = key($extensions)) {
            $options['output'] .= '.'.$extension;
        }

        // output --> target url
        $asset->setTargetPath(str_replace('*', $options['name'], $options['output']));

        // apply workers and return
        return $this->applyWorkers($asset);
    }

    public function generateAssetName($inputs, $filters, $options = [])
    {
        foreach (array_diff(array_keys($options), array('output', 'debug', 'root')) as $key) {
            unset($options[$key]);
        }

        ksort($options);

        return substr(sha1(serialize($inputs).serialize($filters).serialize($options)), 0, 7);
    }

    public function getLastModified(AssetInterface $asset)
    {
        $mtime = 0;
        foreach ($asset instanceof AssetCollectionInterface ? $asset : array($asset) as $leaf) {
            $mtime = max($mtime, $leaf->getLastModified());

            if (!$filters = $leaf->getFilters()) {
                continue;
            }

            $prevFilters = [];
            foreach ($filters as $filter) {
                $prevFilters[] = $filter;

                if (!$filter instanceof DependencyExtractorInterface) {
                    continue;
                }

                // extract children from leaf after running all preceeding filters
                $clone = clone $leaf;
                $clone->clearFilters();
                foreach (array_slice($prevFilters, 0, -1) as $prevFilter) {
                    $clone->ensureFilter($prevFilter);
                }
                $clone->load();

                foreach ($filter->getChildren($this, $clone->getContent(), $clone->getSourceDirectory()) as $child) {
                    $mtime = max($mtime, $this->getLastModified($child));
                }
            }
        }

        return $mtime;
    }

    /**
     * Parses an input string string into an asset.
     *
     * The input string can be one of the following:
     *
     *  * A reference:     If the string starts with an "at" sign it will be interpreted as a reference to an asset in the asset manager
     *  * An absolute URL: If the string contains "://" or starts with "//" it will be interpreted as an HTTP asset
     *  * A glob:          If the string contains a "*" it will be interpreted as a glob
     *  * A path:          Otherwise the string is interpreted as a filesystem path
     *
     * Both globs and paths will be absolutized using the current root directory.
     *
     * @param string $input   An input string
     * @param array  $options An array of options
     *
     * @return AssetInterface An asset
     */
    protected function parseInput($input, array $options = [])
    {
        if ('@' == $input[0]) {
            return $this->createAssetReference(substr($input, 1));
        }

        if (false !== strpos($input, '://') || 0 === strpos($input, '//')) {
            return $this->createHttpAsset($input, $options['vars']);
        }

        if (self::isAbsolutePath($input)) {
            if ($root = self::findRootDir($input, $options['root'])) {
                $path = ltrim(substr($input, strlen($root)), '/');
            } else {
                $path = null;
            }
        } else {
            $root  = $this->root;
            $path  = $input;
            $input = $this->root.'/'.$path;
        }

        if (false !== strpos($input, '*')) {
            return $this->createGlobAsset($input, $root, $options['vars']);
        }

        return $this->createFileAsset($input, $root, $path, $options['vars']);
    }

    protected function createAssetCollection(array $assets = [], array $options = [])
    {
        return new AssetCollection($assets, [], null, isset($options['vars']) ? $options['vars'] : []);
    }

    protected function createAssetReference($name)
    {
        if (!$this->am) {
            throw new \LogicException('There is no asset manager.');
        }

        return new AssetReference($this->am, $name);
    }

    protected function createHttpAsset($sourceUrl, $vars)
    {
        return new HttpAsset($sourceUrl, [], false, $vars);
    }

    protected function createGlobAsset($glob, $root = null, $vars)
    {
        return new GlobAsset($glob, [], $root, $vars);
    }

    protected function createFileAsset($source, $root = null, $path = null, $vars)
    {
        return new FileAsset($source, [], $root, $path, $vars);
    }

    protected function getFilter($name)
    {
        if (!$this->fm) {
            throw new \LogicException('There is no filter manager.');
        }

        return $this->fm->get($name);
    }

    /**
     * Filters an asset collection through the factory workers.
     *
     * Each leaf asset will be processed first, followed by the asset
     * collection itself.
     *
     * @param AssetCollectionInterface $asset An asset collection
     *
     * @return AssetCollectionInterface
     */
    private function applyWorkers(AssetCollectionInterface $asset)
    {
        foreach ($asset as $leaf) {
            foreach ($this->workers as $worker) {
                $retval = $worker->process($leaf, $this);

                if ($retval instanceof AssetInterface && $leaf !== $retval) {
                    $asset->replaceLeaf($leaf, $retval);
                }
            }
        }

        foreach ($this->workers as $worker) {
            $retval = $worker->process($asset, $this);

            if ($retval instanceof AssetInterface) {
                $asset = $retval;
            }
        }

        return $asset instanceof AssetCollectionInterface ? $asset : $this->createAssetCollection(array($asset));
    }

    private static function isAbsolutePath($path)
    {
        return '/' == $path[0] || '\\' == $path[0] || (3 < strlen($path) && ctype_alpha($path[0]) && $path[1] == ':' && ('\\' == $path[2] || '/' == $path[2]));
    }

    /**
     * Loops through the root directories and returns the first match.
     *
     * @param string $path  An absolute path
     * @param array  $roots An array of root directories
     *
     * @return string|null The matching root directory, if found
     */
    private static function findRootDir($path, array $roots)
    {
        foreach ($roots as $root) {
            if (0 === strpos($path, $root)) {
                return $root;
            }
        }
    }
}
