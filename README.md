
Phug Tester
===========

What is Phug Tester?
--------------------

The Phug tester allow you to write unit tests easily for your Phug templates
and get coverage of your tests.

Installation
------------

Install via Composer

```bash
composer require phug/tester
```

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
