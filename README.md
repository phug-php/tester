
Phug Renderer
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

```php
<?php

class MyTemplatesTest extends Phug\Tester\TestCase
{
    public function testContactView()
    {
        self::assertRenderToFile('tests/expected-result.html', 'views/contact.pug', [
            'title' => 'Add some locals',
        ]);
    }
}
```
