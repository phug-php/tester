<?php

namespace Phug\Tester;

use Phug\Renderer;

trait TestCaseTrait
{
    /**
     * @var Renderer
     */
    protected $renderer;

    /**
     * @param string     $file
     * @param array      $locals
     * @param array|null $options
     *
     * @throws \Phug\RendererException
     *
     * @return string
     */
    protected function renderFile($file, $locals = [], $options = null) : string
    {
        if ($options) {
            $this->renderer->setOptions($options);
            $this->renderer->initCompiler();
            $this->renderer->initAdapter();
        }

        return $this->renderer->renderFile($file, $locals);
    }

    /**
     * @return array
     */
    protected function getPaths()
    {
        return ['views'];
    }

    /**
     * @return array
     */
    protected function getExtensions() : array
    {
        return ['', '.pug', '.jade'];
    }

    /**
     * @return Renderer|string
     */
    protected function getRenderer()
    {
        return Renderer::class;
    }

    /**
     * @return array
     */
    protected function getRendererOptions(string $cacheDirectory = null) : array
    {
        return [
            'extensions' => (array) $this->getExtensions(),
            'paths'      => (array) $this->getPaths(),
            'debug'      => true,
            'cache_dir'  => $cacheDirectory ?: sys_get_temp_dir().'/pug-cache-'.mt_rand(0, 9999999),
        ];
    }

    /**
     * @throws \Phug\RendererException
     */
    protected function setUpCoverage()
    {
        $this->renderer = Coverage::get()->createRenderer($this->getRenderer(), $this->getRendererOptions());
    }

    protected function tearDownCoverage()
    {
        Coverage::get()->storeCoverage(xdebug_get_code_coverage());
    }

    /**
     * @throws \Phug\RendererException
     */
    protected function setUp()
    {
        if (method_exists(parent::class, 'setUp')) {
            parent::setUp();
        }
        $this->setUpCoverage();
    }

    protected function tearDown()
    {
        if (method_exists(parent::class, 'tearDown')) {
            parent::tearDown();
        }
        $this->tearDownCoverage();
    }
}
