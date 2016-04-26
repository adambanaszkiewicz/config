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

namespace Requtize\Config\Loader;

use RuntimeException;
use Spyc;

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

        if(! class_exists('Spyc'))
        {
            throw new RuntimeException('Spyc library must be installed.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load()
    {
        if(! is_file($this->filepath))
        {
            throw new RuntimeException('File "'.$this->filepath.'" cannot be found.');
        }

        return Spyc::YAMLLoad($this->filepath);
    }
}
