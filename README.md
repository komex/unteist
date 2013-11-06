#Unteist

Unteist is a unit test framework for developers and testers that makes the writing of tests easy and gets its result more quickly than ever.
It does not load all tests collections to memory but does it step-by-step. One process - one test case in memory. You can use up to 10 processes for testing.
Framework is more flexible in configuration of tests, test cases and suites than "[the de-facto standard for unit testing in PHP projects](https://github.com/sebastianbergmann/phpunit)".

[![Build Status](https://travis-ci.org/komex/unteist.png?branch=develop)](https://travis-ci.org/komex/unteist)

## Requirements

* Unteist requires PHP 5.4 (or later).
* (Optional) [pcntl](http://www.php.net/manual/en/book.pcntl.php) module if you want to run tests in multiple processes.

## Installation

To add Unteist as a dependency to your project, simply add a dependency on `komex/unteist` to your project's `composer.json` file. Here is a minimal example of a `composer.json` file:

```json
    {
        "require": {
            "komex/unteist": "1.0.*"
        }
    }
```
Unteist may generate reports in HTML format. Right now, reports are very simple, but it may be more complex in future.
So, framework needs [Bootstrap](http://getbootstrap.com/2.3.2/), [lessphp](http://leafo.net/lessphp/) and jQuery for working.

## Writing tests

You can place your tests in any directory as you wish, but storing it in `tests` directory is a good practice.
There is no problems with understanding how to write tests if you have already worked with [PHPUnit](http://phpunit.de/manual/current/en/writing-tests-for-phpunit.html).

* The tests for a class `Class` go into a class `ClassTest`.
* `ClassTest` inherits from `\Unteist\TestCase`.
* The tests are public methods that are named `test*`.
Alternatively, you can use the `@test` annotation in a method's docblock to mark it as a test method.
* Inside the test methods, assertion methods such as `\Unteist\Assert\Assert::equals()` are used to assert that an actual value matches an expected value.

### Example

```php
<?php

use \Unteist\Assert\Assert;

class StackTest extends \Unteist\TestCase
{
    public function testPushAndPop()
    {
        $stack = array();
        Assert::equals(0, count($stack));

        array_push($stack, 'foo');
        Assert::equals('foo', $stack[count($stack)-1]);
        Assert::equals(1, count($stack));

        Assert::equals('foo', array_pop($stack));
        Assert::equals(0, count($stack));
    }
}
```

## Features

* Run test cases in separated processes (up to 10 processes per suite);
* Lazy loading test cases to memory. Loads only tests with which we work and frees memory when test case is done;
* Flexible configuration: you are able to change global settings and settings for each suite;
* Multiple dependencies;
* Any methods may be used before and after case or test;
* Test case have two types of data storages:
    * local (access only from tests in same case);
    * global (shared storage for all cases in single process);
* Project are uses composer and based on PSR-0 standart, uses [Symfony components](http://symfony.com/doc/current/components/index.html) and very simple to extend;
* Register your own listeners, use Unteist events and generate custom reports.

## What next

* Store you configuration in different formats (yml, xml, json or all together);
* A lot of new kind of class and methods filters (like namespace filter, mask filter);
* Code Coverage Analysis.

## License

[![Creative Commons License](http://i.creativecommons.org/l/by-sa/3.0/88x31.png)](http://creativecommons.org/licenses/by-sa/3.0/)<br/>
Unteist by [Andrey Kolchenko](https://github.com/komex) is licensed under a [Creative Commons Attribution-ShareAlike 3.0 Unported License](http://creativecommons.org/licenses/by-sa/3.0/).<br/>
Based on a work at [https://github.com/komex/unteist](https://github.com/komex/unteist).