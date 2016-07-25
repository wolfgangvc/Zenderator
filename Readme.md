# Zenderator

## Overview
Zenderator is a mechanism for generating assets based upon a pre-existing schema.

### Generated Assets
Zenderator optionally generates:

* Models
* Controllers
* Services
* Routes
* API Endpoints
* Dependency Injection entries for the above
* Unit tests (Models/Services/Api)

You can configure which of these assets it generates by configuring `zenderator.yml` inside your project.

### Code Standards

One of the other processes that Zenderator does, as part of its operation, is to enforce `psr2` and some other coding standards. 
It does this by running some automated scripts against code it has generated, as well as code written atop of its Models/Controllers/what-have-you.

Ontop of the PSR2 standards, a second suite of automated cleaning tools are run

## Basic Principles

// TODO

### Schema interrogation

// TODO

### Model/Service generation

// TODO

### Generic Controllers

// TODO

### Test generation

Zenderator generates unit tests, ideally for 95%+ coverage of its own generated assets. The aspirational end-goal for generated assets is 100% coverage.
 
Zenderator tests are written in [PHPUnit](https://phpunit.de/manual/current/en/index.html). PHPUnit is a dependency of Zenderator.

All Zenderator tests should be extending from `Zenderator\Test\BaseTestCase`, or `Zenderator\Test\RoutesTestCase` if the test needs to access an endpoint via the API for integration testing.
 
#### Running Tests

To run tests, by default, is very simple, assuming your phpunit.xml/phpunit.xml.dist is configured correctly.

    `./vendor/bin/phpunit`

#### API tests

The API can be tested without first starting a server and running curl requests against the API, as would have been traditional previously.
Instead, we can create [PSR7 Messages](http://www.php-fig.org/psr/psr-7/) and send those into the Slim Router, and have it respond in a manner similar to how it would for a connection into Apache.
This can be seen in `Zenderator\Test\RoutesTestCase` as the `request()` function.

## Additional Reading & Links

* [PHPUnit](https://phpunit.de/manual/current/en/index.html)
* [PSR2 Coding Standards](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md) - An introduction to the PSR2 standard.
* [PSR4 Autoloader](http://www.php-fig.org/psr/psr-4/) - Autoloading format used by Zenderator and Co, only included here for completeness
* [PSR7 Messages](http://www.php-fig.org/psr/psr-7/) 
* [Twig](http://twig.sensiolabs.org/documentation) - We're using twig for genericized object templates.
* [Slim](http://www.slimframework.com/docs/)
* [Pimple Dependency Injection](http://pimple.sensiolabs.org/) - Pimple is used for our Dependency Injection, and comes for a ride with us as a dependency of Slim 3.x