# chubbyphp-static-file

[![Build Status](https://api.travis-ci.org/chubbyphp/chubbyphp-static-file.png?branch=master)](https://travis-ci.org/chubbyphp/chubbyphp-static-file)
[![Coverage Status](https://coveralls.io/repos/github/chubbyphp/chubbyphp-static-file/badge.svg?branch=master)](https://coveralls.io/github/chubbyphp/chubbyphp-static-file?branch=chubbyphp-static-file)
[![Total Downloads](https://poser.pugx.org/chubbyphp/chubbyphp-static-file/downloads.png)](https://packagist.org/packages/chubbyphp/chubbyphp-static-file)
[![Monthly Downloads](https://poser.pugx.org/chubbyphp/chubbyphp-static-file/d/monthly)](https://packagist.org/packages/chubbyphp/chubbyphp-static-file)
[![Latest Stable Version](https://poser.pugx.org/chubbyphp/chubbyphp-static-file/v/stable.png)](https://packagist.org/packages/chubbyphp/chubbyphp-static-file)
[![Latest Unstable Version](https://poser.pugx.org/chubbyphp/chubbyphp-static-file/v/unstable)](https://packagist.org/packages/chubbyphp/chubbyphp-static-file)

## Description

A minimal static file middleware for PSR 15.

## Requirements

 * php: ^7.2
 * [psr/http-factory][2]: ^1.0.1
 * [psr/http-message][3]: ^1.0.1
 * [psr/http-server-handler][4]: ^1.0.1
 * [psr/http-server-middleware][5]: ^1.0.1

## Installation

Through [Composer](http://getcomposer.org) as [chubbyphp/chubbyphp-static-file][1].

## Usage

```php
<?php

declare(strict_types=1);

namespace App;

use Chubbyphp\StaticFile\StaticFileMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/** @var ResponseFactoryInterface $responseFactory */
$responseFactory = ...;

/** @var StreamFactoryInterface $streamFactory */
$streamFactory = ...;

$app = ...;

// add the static file middleware before the routing your PSR15 based framework
$app->add(new StaticFileMiddleware(
    $responseFactory,
    $streamFactory,
    __DIR__ . '/public'
));

```

## Copyright

Dominik Zogg 2019

[1]: https://packagist.org/packages/chubbyphp/chubbyphp-static-file

[2]: https://packagist.org/packages/psr/http-factory
[3]: https://packagist.org/packages/psr/http-message
[4]: https://packagist.org/packages/psr/http-server-handler
[5]: https://packagist.org/packages/psr/http-server-middleware
