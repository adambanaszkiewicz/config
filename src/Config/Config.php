<?php
/**
 * This file is part of the Config package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Copyright (c) 2016 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2016, Adam Banaszkiewicz
 * @link      https://github.com/requtize/config
 */

namespace Requtize\Config;

use RuntimeException;
use Requtize\Config\Loader\LoaderInterface;
use Requtize\Config\Loader\BaseLoader;

/**
 * @author Adam Banaszkiewicz https://github.com/requtize
 */
class Config implements ConfigInterface
{
    /**
     * Indexes separator.
     */
    const SEPARATOR = '.';

    /**
     * Cache filepath, where You want to save it.
     * @var string
     */
    protected $cacheFilepath;

    /**
     * Store configuration data.
     * @var array
     */
    protected $data = [];

    /**
     * Store array of metadata of each file imported
     * to Config object. Index is filepath and value is an array
     * with filemtime() function result, and the parent filepath
     * from is imported.
     * @var array
     */
    protected $metadata = [];

    /**
     * Store information, if any of imported files is included
     * again (before taken from Cache file). This prevents from
     * save Cache file when none of files was updated.
     * @var boolean
     */
    protected $anyFileChanged = false;

    /**
     * Store loaders, for load all configs again, if some of currently
     * loading configs arent't fresh.
     * @var array
     */
    protected $loaders = [];

    /**
     * Constructor.
     * @param string $cacheFilepath Cache filepath.
     */
    public function __construct($cacheFilepath = null)
    {
        $this->cacheFilepath = $cacheFilepath;

        $this->resolveCacheData();
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function isAnyFileChanged()
    {
        return $this->anyFileChanged;
    }

    /**
     * {@inheritdoc}
     */
    public function appendFromLoader(LoaderInterface $loader)
    {
        $filepath = $loader->getFilepath();

        $this->loaders[$filepath] = $loader;

        if($this->isFresh($filepath) === false)
        {
            $data = $loader->load(true);

            if(isset($this->metadata[$filepath]))
            {
                $this->removeIndexes($this->metadata[$filepath]['indexes']);
            }

            $this->data = array_merge($this->data, $data);

            $this->resolveImports($loader);

            $this->metadata[$filepath] = [
                'time'    => $loader->getModificationTime(),
                'parent'  => $loader->getParentFilepath(),
                'indexes' => $this->getIndexes($data),
                'imports' => isset($data['imports']) ? $data['imports'] : []
            ];

            $this->anyFileChanged = true;
        }

        foreach($this->metadata as $name => $file)
        {
            if($file['parent'] === $filepath && $this->isFresh($name) === false)
            {
                $this->appendFromLoader(BaseLoader::factory($name)->setParentFilepath($filepath));
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function import($filepath)
    {
        return $this->appendFromLoader(BaseLoader::factory($filepath));
    }

    /**
     * {@inheritdoc}
     */
    public function merge(ConfigInterface $config)
    {
        $this->data = array_merge($this->data, $config->all());
        $this->metadata = array_merge($this->metadata, $config->getMetadata());

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($path, $default = null)
    {
        if($this->has($path))
        {
            return $this->access($path);
        }
        else
        {
            return $default;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function set($path, $value)
    {
        $access = & $this->data;
        $keys   = explode(self::SEPARATOR, $path);

        while(count($keys) > 0)
        {
            if(count($keys) === 1)
            {
                if(is_array($access))
                {
                    $access[array_shift($keys)] = $value;
                }
                else
                {
                    throw new RuntimeException("Can not set value at this path ($path) because is not array.");
                }
            }
            else
            {
                $key = array_shift($keys);

                if(! isset($access[$key]))
                {
                    $access[$key] = array();
                }

                $access = & $access[$key];
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        $data = $this->data;

        foreach(explode(self::SEPARATOR, $path) as $segment)
        {
            if(isset($data[$segment]))
            {
                $data = $data[$segment];

                continue;
            }
            else
            {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function access($path)
    {
        $data = $this->data;

        foreach(explode(self::SEPARATOR, $path) as $segment)
        {
            if(isset($data[$segment]))
            {
                $data = $data[$segment];
            }
            else
            {
                return null;
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($filepath)
    {
        if(isset($this->metadata[$filepath]['time']) === false)
        {
            return false;
        }

        if(is_file($filepath) === false)
        {
            return 0;
        }

        $mtime = filemtime($filepath);

        if($this->metadata[$filepath]['time'] < $mtime)
        {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveImports(LoaderInterface $loader)
    {
        $filepath = $loader->getFilepath();

        if(isset($this->data['imports']['files']))
        {
            if(isset($this->metadata[$filepath]['imports']['files']))
            {
                $removed = array_diff($this->metadata[$filepath]['imports']['files'], $this->data['imports']['files']);
                $added   = array_diff($this->data['imports']['files'], $this->metadata[$filepath]['imports']['files']);

                $this->removeConfig($loader, $removed);

                foreach($added as $path)
                {
                    if($key = array_search($path, $this->data['imports']['files']) !== false)
                    {
                        unset($this->data['imports']['files'][$key]);
                        unset($this->metadata[$path]);
                    }
                }
            }

            $loaders = [];

            foreach($this->data['imports']['files'] as $file)
            {
                $path = $this->createFilepath($loader, $file);

                if(is_file($path) === false)
                {
                    throw new RuntimeException('Imported config file "'.$path.'" does not exists.');
                }

                $loaders[] = BaseLoader::factory($path)->setParentFilepath($filepath);
            }

            unset($this->data['imports']);

            /**
             * First we create loaders, and after remove imports, append these loaders
             * to object. This prevent recursion loop.
             */
            foreach($loaders as $loader)
            {
                $this->appendFromLoader($loader);
            }
        }
        else
        {
            if(isset($this->metadata[$filepath]['imports']['files']))
            {
                foreach($this->metadata[$filepath]['imports']['files'] as $file)
                {
                    $path = $this->createFilepath($loader, $file);

                    if(isset($this->metadata[$path]))
                    {
                        $this->removeIndexes($this->metadata[$path]['indexes']);
                        unset($this->metadata[$path]);
                    }
                }
            }
        }

        return $this;
    }

    protected function removeConfig(LoaderInterface $loader, array $filepaths)
    {
        foreach($filepaths as $file)
        {
            $path = $this->createFilepath($loader, $file);

            if(isset($this->metadata[$path]))
            {
                $this->removeIndexes($this->metadata[$path]['indexes']);
                unset($this->metadata[$path]);
            }
        }
    }

    protected function createFilepath(LoaderInterface $loader, $file)
    {
        $dir = pathinfo($loader->getFilepath(), PATHINFO_DIRNAME);

        return realpath("$dir/$file");
    }

    /**
     * {@inheritdoc}
     */
    public function setCacheFilepath($cacheFilepath)
    {
        $this->cacheFilepath = $cacheFilepath;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheFilepath()
    {
        return $this->cacheFilepath;
    }

    /**
     * {@inheritdoc}
     */
    public function saveToCache()
    {
        if($this->cacheFilepath === null)
        {
            return null;
        }

        if(is_file($this->cacheFilepath) === false)
        {
            $directory = pathinfo($this->cacheFilepath, PATHINFO_DIRNAME);

            if(is_dir($directory) === false)
            {
                mkdir($directory, 0770, true);
            }
        }

        if($this->anyFileChanged)
        {
            return file_put_contents($this->cacheFilepath, "<?php return ['meta' => ".var_export($this->metadata, true).", 'data' => ".var_export($this->data, true)."];");
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveCacheData()
    {
        $data = [];

        if(is_file($this->cacheFilepath))
        {
            $data = include $this->cacheFilepath;
        }

        if(isset($data['data']))
        {
            $this->data = $data['data'];
        }

        if(isset($data['meta']))
        {
            $this->metadata = $data['meta'];
        }

        return $this;
    }

    protected function getIndexes(array $data)
    {
        return array_keys($data);
    }

    protected function removeIndexes(array $indexes)
    {
        foreach($indexes as $index)
        {
            unset($this->data[$index]);
        }
    }
}
