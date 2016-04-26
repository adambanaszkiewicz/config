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

/**
 * @author Adam Banaszkiewicz https://github.com/requtize
 */
interface LoaderInterface
{
    /**
     * Imports file, parse it and returns its content as array.
     * @return array Parsed data from file.
     */
    public function load();

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
     * Sets path to parent config file, which containes
     * it in imports.file index.
     * @param string $parentFilepath
     * @return self
     */
    public function setParentFilepath($parentFilepath);

    /**
     * Gets parent filepath.
     * @return string
     */
    public function getParentFilepath();

    /**
     * Gets file modification time.
     * @return integer
     */
    public function getModificationTime();
}
