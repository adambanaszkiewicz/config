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

    protected function getCacheFilepath()
    {
        return __DIR__.'/../../cache/cache-file.php';
    }

    public function testContructor()
    {
        /**
         * Empty contructor.
         */
        $config = new Config;

        $this->assertNull($config->getCacheFilepath());
        $this->assertNull($config->getFreshFile());


        /**
         * Constructor with cache filepath.
         */
        $cacheFile = $this->getCacheFilepath();
        $this->tmpFiles[] = $cacheFile;

        $config = new Config($cacheFile);

        $this->assertEquals($cacheFile, $config->getCacheFilepath());
        $this->assertNotNull($config->getFreshFile());
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

    public function testImportWithImportInsideFile()
    {
        /**
         * Import for Yaml files.
         */
        $basepath = realpath(__DIR__.'/../../../resources');
        $config = new Config;
        $config->import(realpath($basepath.'/full-same-yaml.yaml'));

        $this->assertEquals([
            realpath($basepath.'/full-same-yaml.yaml'),
            realpath($basepath.'/custom-yaml.yaml')
        ], $config->getParsedFiles());

        $config->import(realpath($basepath.'/single.ini'));

        $this->assertEquals([
            realpath($basepath.'/full-same-yaml.yaml'),
            realpath($basepath.'/custom-yaml.yaml'),
            realpath($basepath.'/single.ini')
        ], $config->getParsedFiles());


        /**
         * Import for Yaml files.
         */
        $basepath = realpath(__DIR__.'/../../../resources');
        $config = new Config;
        $config->import(realpath($basepath.'/full-same-php.php'));

        $this->assertEquals([
            realpath($basepath.'/full-same-php.php'),
            realpath($basepath.'/custom-php.php')
        ], $config->getParsedFiles());

        $config->import(realpath($basepath.'/single.ini'));

        $this->assertEquals([
            realpath($basepath.'/full-same-php.php'),
            realpath($basepath.'/custom-php.php'),
            realpath($basepath.'/single.ini')
        ], $config->getParsedFiles());



        /**
         * Import for Yaml files.
         */
        $basepath = realpath(__DIR__.'/../../../resources');
        $config = new Config;
        $config->import(realpath($basepath.'/full-same-ini.ini'));

        $this->assertEquals([
            realpath($basepath.'/full-same-ini.ini'),
            realpath($basepath.'/custom-ini.ini')
        ], $config->getParsedFiles());

        $config->import(realpath($basepath.'/single.php'));

        $this->assertEquals([
            realpath($basepath.'/full-same-ini.ini'),
            realpath($basepath.'/custom-ini.ini'),
            realpath($basepath.'/single.php')
        ], $config->getParsedFiles());
    }

    public function testImportMultipleFiles()
    {
        $basepath = realpath(__DIR__.'/../../../resources');
        $config = new Config;
        $config->import([
            realpath($basepath.'/single.php'),
            realpath($basepath.'/single.ini'),
            realpath($basepath.'/single.yaml')
        ]);

        $this->assertNotEquals('single php', $config->get('single'));
        $this->assertNotEquals('single ini', $config->get('single'));
        $this->assertEquals('single yaml', $config->get('single'));

        $this->assertEquals([
            realpath($basepath.'/single.php'),
            realpath($basepath.'/single.ini'),
            realpath($basepath.'/single.yaml')
        ], $config->getParsedFiles());
    }

    public function testImportWithCache()
    {
        $basepath  = realpath(__DIR__.'/../../../resources');
        $cacheFile = $this->getCacheFilepath();

        $parseAndAssertEmpty = function () use ($basepath, $cacheFile) {
            $config = new Config($cacheFile);
            $config->import(realpath($basepath.'/full-same-yaml.yaml'));
            // Parsed files are empty, so there is no need to parse them again.
            $this->assertEquals([], $config->getParsedFiles());

            $config->getFreshFile()->writeMetadataFile();
            $config->saveToCache();
            unset($config);
        };

        $parseTouchAndAssertNotEmpty = function ($file) use ($basepath, $cacheFile) {
            touch($file, time() + 10);
            $config = new Config($cacheFile);
            $config->import(realpath($basepath.'/full-same-yaml.yaml'));
            $this->assertNotEquals([], $config->getParsedFiles());

            $config->getFreshFile()->writeMetadataFile();
            $config->saveToCache();
            unset($config);
        };

        $config = new Config($cacheFile);
        $config->getFreshFile()->writeMetadataFile();
        $config->saveToCache();

        $this->tmpFiles[] = $cacheFile;
        $this->tmpFiles[] = $config->getFreshFile()->getCacheFilepath();
        unset($config);
        $this->removeTmpFiles();


        $config = new Config($cacheFile);
        $config->import(realpath($basepath.'/full-same-yaml.yaml'));
        // Parsed files are NOT empty, so these files were fresh and need to be parsed.
        $this->assertNotEquals([], $config->getParsedFiles());
        $this->assertEquals('value', $config->get('customYaml.key'));

        $config->getFreshFile()->writeMetadataFile();
        $config->saveToCache();
        unset($config);

        $parseAndAssertEmpty();

        // Touch main file should cause parse all files
        $parseTouchAndAssertNotEmpty($basepath.'/full-same-yaml.yaml');

        $parseAndAssertEmpty();

        // Touch imported file should cause parse all files
        $parseTouchAndAssertNotEmpty($basepath.'/custom-yaml.yaml');

        $parseAndAssertEmpty();
    }

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
            if(is_file($file))
            {
                unlink($file);
            }
        }
    }
}
