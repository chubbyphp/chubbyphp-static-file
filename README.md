# chubbyphp-static-file

[![CI](https://github.com/chubbyphp/chubbyphp-static-file/workflows/CI/badge.svg?branch=master)](https://github.com/chubbyphp/chubbyphp-static-file/actions?query=workflow%3ACI)
[![Coverage Status](https://coveralls.io/repos/github/chubbyphp/chubbyphp-static-file/badge.svg?branch=master)](https://coveralls.io/github/chubbyphp/chubbyphp-static-file?branch=master)
[![Infection MSI](https://badge.stryker-mutator.io/github.com/chubbyphp/chubbyphp-static-file/master)](https://dashboard.stryker-mutator.io/reports/github.com/chubbyphp/chubbyphp-static-file/master)
[![Latest Stable Version](https://poser.pugx.org/chubbyphp/chubbyphp-static-file/v/stable.png)](https://packagist.org/packages/chubbyphp/chubbyphp-static-file)
[![Total Downloads](https://poser.pugx.org/chubbyphp/chubbyphp-static-file/downloads.png)](https://packagist.org/packages/chubbyphp/chubbyphp-static-file)
[![Monthly Downloads](https://poser.pugx.org/chubbyphp/chubbyphp-static-file/d/monthly)](https://packagist.org/packages/chubbyphp/chubbyphp-static-file)

[![bugs](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-static-file&metric=bugs)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-static-file)
[![code_smells](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-static-file&metric=code_smells)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-static-file)
[![coverage](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-static-file&metric=coverage)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-static-file)
[![duplicated_lines_density](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-static-file&metric=duplicated_lines_density)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-static-file)
[![ncloc](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-static-file&metric=ncloc)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-static-file)
[![sqale_rating](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-static-file&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-static-file)
[![alert_status](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-static-file&metric=alert_status)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-static-file)
[![reliability_rating](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-static-file&metric=reliability_rating)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-static-file)
[![security_rating](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-static-file&metric=security_rating)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-static-file)
[![sqale_index](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-static-file&metric=sqale_index)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-static-file)
[![vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=chubbyphp_chubbyphp-static-file&metric=vulnerabilities)](https://sonarcloud.io/dashboard?id=chubbyphp_chubbyphp-static-file)


## Description

A minimal static file middleware for PSR 15.

## Requirements

 * php: ^8.0
 * [psr/http-factory][2]: ^1.0.1
 * [psr/http-message][3]: ^1.0.1
 * [psr/http-server-handler][4]: ^1.0.1
 * [psr/http-server-middleware][5]: ^1.0.1

## Installation

Through [Composer](http://getcomposer.org) as [chubbyphp/chubbyphp-static-file][1].

```sh
composer require chubbyphp/chubbyphp-static-file "^1.2"
```

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

Dominik Zogg 2022

[1]: https://packagist.org/packages/chubbyphp/chubbyphp-static-file

[2]: https://packagist.org/packages/psr/http-factory
[3]: https://packagist.org/packages/psr/http-message
[4]: https://packagist.org/packages/psr/http-server-handler
[5]: https://packagist.org/packages/psr/http-server-middleware
