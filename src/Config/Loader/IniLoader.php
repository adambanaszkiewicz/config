<?php
/**
 * This file is part of the Config package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Copyright (c) 2016 - 2017 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2016 - 2017, Adam Banaszkiewicz
 * @link      https://github.com/requtize/config
 */

namespace Requtize\Config\Loader;

use RuntimeException;

/**
 * @author Adam Banaszkiewicz https://github.com/requtize
 */
class IniLoader extends BaseLoader
{
    protected $parseSections;

    /**
     * Constructor.
     * @param string  $filepath      Configuration filepath to parse.
     * @param boolean $parseSections Parser should parse INI sections?
     */
    public function __construct($filepath, $parseSections = true)
    {
        $this->parseSections = $parseSections;

        parent::__construct($filepath);
    }

    /**
     * {@inheritdoc}
     */
    public function load($forceNew = false)
    {
        if(! is_file($this->filepath))
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

        return $this->cachedData = parse_ini_file($this->filepath, $this->parseSections);
    }
}
