
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
./phug-tester --pug-coverage-threshold=90 --pug-text-coverage --pug-html-coverage=coverage/pug
```

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
