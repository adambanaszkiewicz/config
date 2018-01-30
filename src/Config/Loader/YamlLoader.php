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

namespace Requtize\Config\Loader;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * @author Adam Banaszkiewicz https://github.com/requtize
 */
class YamlLoader extends BaseLoader
{
    /**
     * {@inheritdoc}
     */
    public function __construct($filepath)
    {
        parent::__construct($filepath);

        if(class_exists('Symfony\Component\Yaml\Yaml') === false)
        {
            throw new RuntimeException('Symfony Yaml library must be installed.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load($forceNew = false)
    {
        if(is_file($this->filepath) === false)
        {
            throw new RuntimeException('File "'.$this->filepath.'" cannot be found.');
        }

        if($forceNew === true)
        {
            $this->cachedData = null;
        }

        if($this->cachedData)
        {
            return $this->cachedData;
        }

        return $this->cachedData = Yaml::parseFile($this->filepath);
    }
}
