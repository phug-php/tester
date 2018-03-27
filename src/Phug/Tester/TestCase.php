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
     * @return string
     *
     * @throws \Phug\RendererException
     */
    protected function renderFile($file, $locals = [], $options = null)
    {
        if ($options) {
            $this->renderer->setOptions($options);
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
}
