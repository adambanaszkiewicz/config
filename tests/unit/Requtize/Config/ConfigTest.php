<?php

use Requtize\Config\Config;
use Requtize\Config\Loader\PhpLoader;

class ConfigTest extends PHPUnit_Framework_TestCase
{
    protected $tmpFiles = [];

    protected function tearDown()
    {
        $this->removeTmpFiles();
    }

    public function testContructor()
    {
        $cacheFile = '/home/user/www/domains/website.com/http_docs/Cache/file.php';

        $config = new Config($cacheFile);

        $this->assertEquals($cacheFile, $config->getCacheFilepath());
    }

    public function testGetSetHas()
    {
        $config = new Config;

        $this->assertEquals([], $config->all());

        $config->set('simpleIndex', 'simpleIndex');

        $this->assertEquals(['simpleIndex' => 'simpleIndex'], $config->all());
        $this->assertEquals('simpleIndex', $config->get('simpleIndex'));

        $config->set('one1.two1', 'one1-two1');

        $this->assertEquals('one1-two1', $config->get('one1.two1'));

        $config->set('one2.two2.three2.four2.aser.343v2.4v5y673vc4.rty3.56une.asdfsdf.0.0.0.3456.1', 'Works');

        // Last index get
        $this->assertEquals('Works', $config->get('one2.two2.three2.four2.aser.343v2.4v5y673vc4.rty3.56une.asdfsdf.0.0.0.3456.1'));

        // Array get
        $this->assertEquals(['3456' => ['1' => 'Works']], $config->get('one2.two2.three2.four2.aser.343v2.4v5y673vc4.rty3.56une.asdfsdf.0.0.0'));


        $this->assertEquals(false, $config->has('__non-existed-index__'));
        $this->assertEquals(true, $config->has('simpleIndex'));
        $this->assertEquals(true, $config->has('one1.two1'));
        $this->assertEquals(false, $config->has('one1.twol')); // L instead of 1 !!!
        $this->assertEquals(true, $config->has('one2.two2.three2.four2.aser.343v2.4v5y673vc4.rty3.56une.asdfsdf.0.0.0.3456.1'));
        $this->assertEquals(true, $config->has('one2.two2.three2.four2.aser.343v2.4v5y673vc4.rty3.56une.asdfsdf.0'));
    }

    public function testImportSimple()
    {
        $config = new Config;

        $this->assertEquals([], $config->all());

        $file = <<<EOF
<?php return [
    'trueType' => true
];
EOF;

        $config->import($this->createTmpFile($file));

        $this->assertNotEquals([], $config->all());
        $this->assertEquals(true, $config->get('trueType'));
        $this->assertEquals(null, $config->get('nullValue'));
        $this->assertEquals(null, $config->get('integer'));

        $file = <<<EOF
<?php return [
    'nullValue' => null,
    'integer' => 1234
];
EOF;

        $config->import($this->createTmpFile($file));

        $this->assertNotEquals([], $config->all());
        $this->assertEquals(true, $config->get('trueType'));
        $this->assertEquals(null, $config->get('nullValue'));
        $this->assertEquals(1234, $config->get('integer'));
    }

    public function testAppendFromLoaderWithImport()
    {
        $config = new Config;

        $file = <<<EOF
<?php return [
    'child' => true
];
EOF;

        $pathChild = $this->createTmpFile($file);
        $pathChildName = pathinfo($pathChild, PATHINFO_BASENAME);

        $file = <<<EOF
<?php return [
    'parent' => true,
    'imports' => [
        '{$pathChildName}'
    ]
];
EOF;

        $path = $this->createTmpFile($file);

        $config->appendFromLoader(new PhpLoader($path));

        // Index from parent
        $this->assertEquals(true, $config->get('parent'));
        // Index from child, imported by class.
        $this->assertEquals(true, $config->get('child'));
    }

    /*public function testImportFromLoaderModificationTimeCheck()
    {
        $reflectionAnyFileChanged = new ReflectionProperty('Requtize\\Config\\Config', 'anyFileChanged');
        $reflectionAnyFileChanged->setAccessible(true);

        $reflectionModificationTimes = new ReflectionProperty('Requtize\\Config\\Config', 'modificationTimes');
        $reflectionModificationTimes->setAccessible(true);

        $config = new Config;

        $file = <<<EOF
<?php return [
    'key' => 'value1'
];
EOF;
        
        /**
         * Without import, no files was changed and index not exists,
         * and returns default value.
         *
        $this->assertEquals(false, $reflectionAnyFileChanged->getValue($config));
        $this->assertEquals('-not-exists-', $config->get('key', '-not-exists-'));

        $path = $this->createTmpFile($file);
        $config->appendFromLoader(new PhpLoader($path));

        /**
         * After importing, some files has changed, and index exists.
         *
        $this->assertEquals(true, $reflectionAnyFileChanged->getValue($config));
        $this->assertEquals('value1', $config->get('key', '-not-exists-'));

        $reflectionAnyFileChanged->setValue($config, false);

        /**
         * Ensure, that we change value of property.
         *
        $this->assertEquals(false, $reflectionAnyFileChanged->getValue($config));

        $config->appendFromLoader(new PhpLoader($path));

        /**
         * After append the same file second time, none of files hase changed,
         * and the value on index is thge same.
         *
        $this->assertEquals(false, $reflectionAnyFileChanged->getValue($config));
        $this->assertEquals('value1', $config->get('key', '-not-exists-'));

        $file = <<<EOF
<?php return [
    'key' => 'value2'
];
EOF;

        file_put_contents($path, $file);

        $reflectionModificationTimes->setValue($config, [$path => [ 'time' => time() - 360, 'parent' => null ]]);

        $config->appendFromLoader(new PhpLoader($path));

        /**
         * After append file third time, some files has changed, and new index
         * value need to be existent.
         *
        $this->assertEquals(true, $reflectionAnyFileChanged->getValue($config));
        $this->assertEquals('value2', $config->get('key', '-not-exists-'));
    }*/

    /*public function testImportFromLoaderChildModificationTimeCheck()
    {
        $reflectionAnyFileChanged = new ReflectionProperty('Requtize\\Config\\Config', 'anyFileChanged');
        $reflectionAnyFileChanged->setAccessible(true);

        $reflectionModificationTimes = new ReflectionProperty('Requtize\\Config\\Config', 'modificationTimes');
        $reflectionModificationTimes->setAccessible(true);

        $config = new Config;

        $file = <<<EOF
<?php return [
    'child' => 'value21'
];
EOF;

        $pathChild = $this->createTmpFile($file);
        $pathChildName = pathinfo($pathChild, PATHINFO_BASENAME);

        $file = <<<EOF
<?php return [
    'parent' => 'value11',
    'imports' => [
        '{$pathChildName}'
    ]
];
EOF;

        $path = $this->createTmpFile($file);
        
        /**
         * Without import, no files was changed and index not exists,
         * and returns default value.
         *
        $this->assertEquals(false, $reflectionAnyFileChanged->getValue($config));
        $this->assertEquals('-not-exists-', $config->get('parent', '-not-exists-'));

        $path = $this->createTmpFile($file);
        $config->appendFromLoader(new PhpLoader($path));

        /**
         * After importing, some files has changed, and index exists.
         *
        $this->assertEquals(true, $reflectionAnyFileChanged->getValue($config));
        $this->assertEquals('value11', $config->get('parent', '-not-exists-'));

        $reflectionAnyFileChanged->setValue($config, false);

        /**
         * Ensure, that we change value of property.
         *
        $this->assertEquals(false, $reflectionAnyFileChanged->getValue($config));

        $config->appendFromLoader(new PhpLoader($path));

        /**
         * After append the same file second time, none of files hase changed,
         * and the value on index is thge same.
         *
        $this->assertEquals(false, $reflectionAnyFileChanged->getValue($config));
        $this->assertEquals('value11', $config->get('parent', '-not-exists-'));

        $file = <<<EOF
<?php return [
    'child' => 'value22'
];
EOF;

        file_put_contents($pathChild, $file);

        $modifications = $config->getModificationTimes();
        $modifications[$pathChild] = [ 'time' => time() - 360, 'parent' => realpath($path) ];

        $reflectionModificationTimes->setValue($config, $modifications);

        $config->appendFromLoader(new PhpLoader($pathChild));

        /**
         * After append file third time, some files has changed, and new index
         * value need to be existent.
         *
        $this->assertEquals(true, $reflectionAnyFileChanged->getValue($config));
        $this->assertEquals('value22', $config->get('child', '-not-exists-'));
    }*/

    public function testMerge()
    {
        $config1 = new Config;

        $file1 = <<<EOF
<?php return [
    'key-unique-1' => 'value-unique-1',
    'key-same' => 'value-same-1'
];
EOF;

        $path1 = $this->createTmpFile($file1);
        $config1->appendFromLoader(new PhpLoader($path1));


        $config2 = new Config;

        $file2 = <<<EOF
<?php return [
    'key-unique-2' => 'value-unique-2',
    'key-same' => 'value-same-2'
];
EOF;

        $path2 = $this->createTmpFile($file2);
        $config2->appendFromLoader(new PhpLoader($path2));


        $config1->merge($config2);

        $this->assertEquals('value-unique-1', $config1->get('key-unique-1'));
        $this->assertEquals('value-unique-2', $config1->get('key-unique-2'));
        $this->assertEquals('value-same-2',   $config1->get('key-same'));
    }

    public function testResolveImports()
    {
        $config = new Config;

        $file1 = <<<EOF
<?php return [ 'child1' => 'value-from-child-1' ];
EOF;

        $child1 = pathinfo($this->createTmpFile($file1), PATHINFO_BASENAME);

        $file2 = <<<EOF
<?php return [ 'child2' => 'value-from-child-2' ];
EOF;

        $child2 = pathinfo($this->createTmpFile($file2), PATHINFO_BASENAME);

        $file3 = <<<EOF
<?php return [ 'child3' => 'value-from-child-3' ];
EOF;

        $child3 = pathinfo($this->createTmpFile($file3), PATHINFO_BASENAME);

        $file4 = <<<EOF
<?php return [ 'child4' => 'value-from-child-4' ];
EOF;

        $child4 = pathinfo($this->createTmpFile($file4), PATHINFO_BASENAME);

        $file = <<<EOF
<?php return [
    'parent' => 'value-from-parent',
    'imports' => [
        '{$child1}',
        '{$child2}',
        '{$child3}',
        '{$child4}'
    ]
];
EOF;

        $path = $this->createTmpFile($file);

        $config->appendFromLoader(new PhpLoader($path));

        $this->assertEquals('value-from-parent', $config->get('parent'));
        $this->assertEquals('value-from-child-1', $config->get('child1'));
        $this->assertEquals('value-from-child-2', $config->get('child2'));
        $this->assertEquals('value-from-child-3', $config->get('child3'));
        $this->assertEquals('value-from-child-4', $config->get('child4'));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testResolveImportsFail()
    {
        $config = new Config;

        $file1 = <<<EOF
<?php return [ 'child1' => 'value-from-child-1' ];
EOF;

        $child1 = pathinfo($this->createTmpFile($file1), PATHINFO_BASENAME);

        $file = <<<EOF
<?php return [
    'parent' => 'value-from-parent',
    'imports' => [
        '{$child1}',
        '_____unexistent-filepath_____.php'
    ]
];
EOF;

        $path = $this->createTmpFile($file);

        $config->appendFromLoader(new PhpLoader($path));
    }

    /*public function testSaveToCache()
    {
        $reflectionAnyFileChanged = new ReflectionProperty('Requtize\\Config\\Config', 'anyFileChanged');
        $reflectionAnyFileChanged->setAccessible(true);

        $config = new Config;

        $file1 = <<<EOF
<?php return [ 'child1' => 'value-from-child-1' ];
EOF;

        $child1 = pathinfo($this->createTmpFile($file1), PATHINFO_BASENAME);

        $file = <<<EOF
<?php return [
    'parent' => 'value-from-parent',
    'imports' => [
        '{$child1}'
    ]
];
EOF;

        // Save file with configuration
        $path = $this->createTmpFile($file);

        // Create empty file for cache data.
        $cacheFile = $this->createTmpFile('');

        $config->setCacheFilepath($cacheFile);
        $config->appendFromLoader(new PhpLoader($path));

        // Before save cache, this file should be empty
        $this->assertEquals('', file_get_contents($cacheFile));

        $config->saveToCache();

        // After save cache, file should not be empty...
        $this->assertNotEquals('', file_get_contents($cacheFile));

        // ...and Config::anyFileChanged should be true
        $this->assertEquals(true, $reflectionAnyFileChanged->getValue($config));

        // Reset cache file and Config::anyFileChanged property
        file_put_contents($cacheFile, '');
        $reflectionAnyFileChanged->setValue($config, false);

        // Ensure that resetting done ok.
        $this->assertEquals(false, $reflectionAnyFileChanged->getValue($config));
        $this->assertEquals('', file_get_contents($cacheFile));

        // Save to cache again and...
        $config->saveToCache();

        // ...should not be saved, because nothing changed, no new files was added.
        $this->assertEquals(false, $reflectionAnyFileChanged->getValue($config));
        $this->assertEquals('', file_get_contents($cacheFile));
    }*/

    protected function createTmpFile($content)
    {
        $path = tempnam(rtrim(sys_get_temp_dir(), '/').'/', 'config');

        file_put_contents($path, $content);

        $this->tmpFiles[] = $path;

        return $path;
    }

    protected function removeTmpFiles()
    {
        foreach($this->tmpFiles as $file)
        {
            unlink($file);
        }
    }
}
