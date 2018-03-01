<?php
/**
 * This file is part of the Config package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Copyright (c) 2016 - 2018 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2016 - 2018, Adam Banaszkiewicz
 * @link      https://github.com/requtize/config
 */

namespace Requtize\Config;

use RuntimeException;
use Requtize\FreshFile\FreshFile;
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
     * @var Requtize\FreshFile\FreshFile
     */
    protected $freshFile;

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
     * Store information, if any of imported files is included
     * again (before taken from Cache file). This prevents from
     * save Cache file when none of files was updated.
     * @var boolean
     */
    protected $anyFileChanged = false;

    /**
     * Store list of parsed files imported both from file and object call.
     * @var array
     */
    protected $parsedFiles = [];

    /**
     * Constructor.
     * @param string $cacheFilepath Cache filepath.
     */
    public function __construct($cacheFilepath = null)
    {
        if(is_string($cacheFilepath))
        {
            $this->setCacheFilepath($cacheFilepath);
            $this->setFreshFile(new FreshFile(pathinfo($this->cacheFilepath, PATHINFO_DIRNAME).'/.fresh-file'));

            $this->resolveCacheData();
        }
    }

    public function __destruct()
    {
        $this->saveToCache();
    }

    /**
     * {@inheritdoc}
     */
    public function appendFromLoader(LoaderInterface $loader, $forceRefresh = false)
    {
        $filepath = $loader->getFilepath();

        if($forceRefresh === true || $this->isFresh($filepath))
        {
            $data = $loader->load(true);

            if(is_array($data))
            {
                $this->data = array_merge($this->data, $data);
            }

            $this->parsedFiles[] = $filepath;

            $importedLoaders = $this->resolveImports($loader);

            $this->anyFileChanged = true;

            if($this->freshFile)
            {
                $collection = [];

                foreach($importedLoaders as $il)
                    $collection[] = $il->getFilepath();

                if($collection !== [])
                {
                    $this->freshFile->setRelatedFiles($filepath, $collection);
                }
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function import($filepath)
    {
        if(is_array($filepath))
        {
            foreach($filepath as $file)
            {
                $this->appendFromLoader(BaseLoader::factory($file));
            }
        }
        elseif(is_string($filepath))
        {
            $this->appendFromLoader(BaseLoader::factory($filepath));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(ConfigInterface $config)
    {
        $this->data = array_merge($this->data, $config->all());

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

    public function getParsedFiles()
    {
        return $this->parsedFiles;
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

    public function setFreshFile(FreshFile $freshFile)
    {
        $this->freshFile = $freshFile;

        return $this;
    }

    public function getFreshFile()
    {
        return $this->freshFile;
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
            return file_put_contents($this->cacheFilepath, "<?php return ".var_export($this->data, true).";");
        }

        return null;
    }

    /**
     * Check if given filepane exists in $this->modificationTimes array
     * and if is fresh. Otherwise return false.
     * @param  string  $filepath Filepath to check if file is fresh.
     * @return boolean
     */
    protected function isFresh($filepath)
    {
        if($this->freshFile)
        {
            return $this->freshFile->isFresh($filepath);
        }
        else
        {
            return true;
        }
    }

    /**
     * Check if in current Config data exists index 'imports.file',
     * and import files from given paths relative to file that
     * contains these imports. At the end removes these indexes.
     * @param  LoaderInterface $loader LoaderInterface object, which
     *                                 contains filewith imports we
     *                                 have to resolve.
     * @return self
     */
    protected function resolveImports(LoaderInterface $loader)
    {
        $filepath = $loader->getFilepath();

        if(isset($this->data['imports']) === false || is_array($this->data['imports']) === false)
        {
            return [];
        }

        $loaders = [];

        foreach($this->data['imports'] as $file)
        {
            $path = $this->createFilepath($loader, $file);

            if(is_file($path) === false)
            {
                throw new RuntimeException('Imported config file "'.$path.'" does not exists.');
            }

            $loaders[] = BaseLoader::factory($path);
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

        return $loaders;
    }

    protected function createFilepath(LoaderInterface $loader, $file)
    {
        $dir = pathinfo($loader->getFilepath(), PATHINFO_DIRNAME);

        return realpath("$dir/$file");
    }

    /**
     * Include Cache file, and take data from it.
     * @return self
     */
    protected function resolveCacheData()
    {
        $data = [];

        if(is_file($this->cacheFilepath))
        {
            $data = include $this->cacheFilepath;
        }

        if(isset($data))
        {
            $this->data = $data;
        }

        return $this;
    }
}
