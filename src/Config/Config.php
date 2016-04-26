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
     * Store array of modification times of each file imported
     * to Config object. Index is filepath and value is an array
     * with filemtime() function result, and the parent filepath
     * from is imported.
     * @var array
     */
    protected $modificationTimes = [];

    /**
     * Store information, if any of imported files is included
     * again (before taken from Cache file). This prevents from
     * save Cache file when none of files was updated.
     * @var boolean
     */
    protected $anyFileChanged = false;

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
    public function getModificationTimes()
    {
        return $this->modificationTimes;
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
        if($this->isFresh($loader->getFilepath()) === false)
        {
            $this->data = array_merge($this->data, $loader->load());

            $this->resolveImports($loader);

            $this->modificationTimes[$loader->getFilepath()] = [
                'time'   => $loader->getModificationTime(),
                'parent' => $loader->getParentFilepath()
            ];

            $this->anyFileChanged = true;
        }

        foreach($this->modificationTimes as $name => $file)
        {
            if($file['parent'] === $loader->getFilepath() && $this->isFresh($name) === false)
            {
                $this->appendFromLoader(BaseLoader::factory($name)->setParentFilepath($loader->getFilepath()));
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
        $this->modificationTimes = array_merge($this->modificationTimes, $config->getModificationTimes());

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
        if(isset($this->modificationTimes[$filepath]['time']) === false)
        {
            return false;
        }

        $mtime = filemtime($filepath);

        if($this->modificationTimes[$filepath]['time'] < $mtime)
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
        if(isset($this->data['imports']['files']))
        {
            $dir = pathinfo($loader->getFilepath(), PATHINFO_DIRNAME);
            $loaders = [];

            foreach($this->data['imports']['files'] as $file)
            {
                $path = "$dir/$file";

                if(is_file($path) === false)
                {
                    throw new RuntimeException('Imported config file "'.$path.'" does not exists.');
                }

                $loaders[] = BaseLoader::factory($path)->setParentFilepath($loader->getFilepath());
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

        return $this;
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

        if($this->anyFileChanged)
        {
            return file_put_contents($this->cacheFilepath, "<?php return ['times' => ".var_export($this->modificationTimes, true).", 'data' => ".var_export($this->data, true)."];");
        }

        return true;
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

        if(isset($data['times']))
        {
            $this->modificationTimes = $data['times'];
        }

        return $this;
    }
}
