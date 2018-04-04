<?php

namespace Phug\Tester;

use Phug\Renderer;

abstract class TestCase extends \PHPUnit\Framework\TestCase
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
    protected function renderFile($file, $locals = [], $options = null)
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
     * @throws \Phug\RendererException
     */
    protected function setUp()
    {
        $this->renderer = Coverage::get()->createRenderer(
            $this->getRenderer(),
            $this->getExtensions(),
            $this->getPaths()
        );
    }

    protected function tearDown()
    {
        parent::tearDown();
        Coverage::get()->storeCoverage(xdebug_get_code_coverage());
    }
}
