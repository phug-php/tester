
Phug Tester
===========

What is Phug Tester?
--------------------

The Phug tester allow you to write unit tests easily for your Phug templates
and get coverage of your tests.

As a [PHPUnit](https://phpunit.de/) extension, you can use all the features,
options and same code you use usually running `phpunit` when your run the
`phug-tester` command.

Installation
------------

Install via Composer

```bash
composer require phug/tester
```

Requirements:
- PHP >= 7.0
- [XDebug PHP extension](https://xdebug.org/) 
- [PHPUnit](https://phpunit.de/) >= 5.7 (installed automatically via composer)

Usage
-----

```shell
./vendor/bin/phug-tester --pug-coverage-threshold=90 --pug-coverage-text --pug-coverage-html=coverage/pug
```

You can couple it with PHP coverage data as phug-tester use PHPUnit
and is compatible with all its options:
```shell
./vendor/bin/phug-tester --coverage-text --pug-coverage-text --coverage-html=coverage --pug-coverage-html=coverage/pug
```

This will output in the CLI both PHP and Pug coverage summaries and
it will dump as HTML PHP coverage in the directory **coverage** and
Pug coverage in the sub-directory **coverage/pug**.

```php
<?php

class MyTemplatesTest extends Phug\Tester\TestCase
{
    public function testContactView()
    {
        $html = $this->renderFile('views/contact.pug', [
            'title' => 'Add some locals',
        ]);
        self::assertContains('Bar', $html);
        self::assertNotContains('Foo', $html);
    }
}
```

The `Phug\Tester\TestCase` is needed to use Pug utils such as
`renderFile` or if you need to get both PHP and Pug coverage in
a single command.

As an alternative if you extend an other class, you can use
the trait:

```php
<?php

class MyTemplatesTest extends MyFramerowk\TestCase
{
    use Phug\Tester\TestCaseTrait;
}
```

Options
-------

`--pug-coverage-text` display Pug coverage summary in the standard
output of the CLI if present.

`--pug-coverage-html` dump coverage data as HTML in a directory, you
have to specify the directory,
example: `--pug-coverage-html=/path/to/output/directory`.

`--pug-coverage-threshold` test the coverage rate against a threshold,
the command will fail if the threshold is not reached (status 1) and
succeed if if does (status 0), you must specify a percentage between
0 and 100, example: `--pug-coverage-threshold=90`

Configuration
-------------

When using `TestCaseTrait` or extending `TestCase`, configuration
methods are available to align the renderer behavior on your app
test needs.

- `getPaths` by default `['views']`, allows you to change base
directories where your templates live.
- `getExtensions` by default `['', '.pug', '.jade']`, allows you
to change file extensions to detect as pug files (and suffixes
that will be tried to be appended automatically). The default
value implies for example that when calling `->renderFile('contact')`
it will look for the files `contact`, `contact.pug` and `contact.jade`.
- `getRenderer` by default `'Phug\Renderer'`, allows you to change
the renderer engine to be used. It can be a class name (then it
will be created on the fly) or it can be an instance used as is.
But if you return an instance, you need to set the options manually
on it.
- `getRendererOptions` by default:
```php
[
   'extensions' => (array) $this->getExtensions(),
   'paths'      => (array) $this->getPaths(),
   'debug'      => true,
   'cache_dir'  => $cacheDirectory ?: sys_get_temp_dir().'/pug-cache-'.mt_rand(0, 9999999),
]
```
`$cacheDirectory` can be passed as an argument of the method.

By returning a new array in this method, you erase and replace all the
options, but by using `array_merge` you can add an merge new options.

For example to add shared variables:
```php
protected function getRendererOptions($cacheDirectory = null)
{
    return array_merge(parent::getRendererOptions($cacheDirectory), [
        'shared_variables' => [
            'locale' => 'en',
            'user'   => new User(),
        ],
    ];
}
```
