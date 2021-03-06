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
    protected function renderFile($file, $locals = [], $options = null): string
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
    protected function getExtensions()
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
     * @param string|null $cacheDirectory
     *
     * @return array
     */
    protected function getRendererOptions($cacheDirectory = null)
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
    protected function setUpCoverage(): void
    {
        $this->renderer = Coverage::get()->createRenderer($this->getRenderer(), $this->getRendererOptions());
    }

    protected function tearDownCoverage(): void
    {
        Coverage::get()->storeCoverage(xdebug_get_code_coverage());
    }

    /**
     * @throws \Phug\RendererException
     */
    protected function setUp(): void
    {
        if (method_exists(parent::class, 'setUp')) {
            parent::setUp();
        }
        $this->setUpCoverage();
    }

    protected function tearDown(): void
    {
        if (method_exists(parent::class, 'tearDown')) {
            parent::tearDown();
        }
        $this->tearDownCoverage();
    }
}
