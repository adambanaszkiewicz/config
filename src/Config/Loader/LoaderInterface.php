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

/**
 * @author Adam Banaszkiewicz https://github.com/requtize
 */
interface LoaderInterface
{
    /**
     * Imports file, parse it and returns its content as array.
     * @param boolean $forceNew Force get new data from file instead of cached in loader.
     * @return array Parsed data from file.
     */
    public function load($forceNew = false);

    /**
     * Sets path to config file.
     * @param string $filepath
     * @return self
     */
    public function setFilepath($filepath);

    /**
     * Gets path to config file.
     * @return string
     */
    public function getFilepath();

    /**
     * Gets file modification time.
     * @return integer
     */
    public function getModificationTime();
}
