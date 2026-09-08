# chubbyphp-static-file

[![CI](https://github.com/chubbyphp/chubbyphp-static-file/actions/workflows/ci.yml/badge.svg)](https://github.com/chubbyphp/chubbyphp-static-file/actions/workflows/ci.yml)
[![Coverage Status](https://coveralls.io/repos/github/chubbyphp/chubbyphp-static-file/badge.svg?branch=master)](https://coveralls.io/github/chubbyphp/chubbyphp-static-file?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fchubbyphp%2Fchubbyphp-static-file%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/chubbyphp/chubbyphp-static-file/master)
[![Latest Stable Version](https://poser.pugx.org/chubbyphp/chubbyphp-static-file/v)](https://packagist.org/packages/chubbyphp/chubbyphp-static-file)
[![Total Downloads](https://poser.pugx.org/chubbyphp/chubbyphp-static-file/downloads)](https://packagist.org/packages/chubbyphp/chubbyphp-static-file)
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

A minimal [PSR-15](https://www.php-fig.org/psr/psr-15/) middleware that serves static files from a public directory.

 * Serves only regular, readable files inside the public directory; path traversal and symlinks pointing outside are rejected.
 * Handles `GET` and `HEAD` requests, any other method is passed to the next handler.
 * Sends `Content-Type` (based on the file extension, `application/octet-stream` as fallback), `Content-Length`, `ETag` and `X-Content-Type-Options: nosniff`.
 * Responds with `304 Not Modified` if the `If-None-Match` header matches the file's `ETag`.
 * Passes the request to the next handler if no matching file exists.

## Requirements

 * php: ^8.3
 * [psr/http-factory][2]: ^1.1
 * [psr/http-message][3]: ^1.1|^2.0
 * [psr/http-server-handler][4]: ^1.0.2
 * [psr/http-server-middleware][5]: ^1.0.2

## Installation

Through [Composer](http://getcomposer.org) as [chubbyphp/chubbyphp-static-file][1].

```sh
composer require chubbyphp/chubbyphp-static-file "^1.4"
```

## Usage

Register the middleware before the routing of your PSR-15 based framework, so that static files are served without hitting a route.

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

$app->add(new StaticFileMiddleware(
    $responseFactory,
    $streamFactory,
    __DIR__ . '/public'
));
```

### Options

The constructor accepts two optional arguments:

 * `$hashAlgorithm` (default: `md5`): the algorithm used to calculate the `ETag`, must be supported by [hash_algos()](https://www.php.net/manual/en/function.hash-algos.php).
 * `$mimetypes` (default: bundled list based on the [Apache mime.types](https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types), regenerate with `php generate-mimetypes.php`): a map of file extension to mime type.

```php
$app->add(new StaticFileMiddleware(
    $responseFactory,
    $streamFactory,
    __DIR__ . '/public',
    'sha256',
    ['css' => 'text/css', 'js' => 'text/javascript', 'png' => 'image/png']
));
```

## Copyright

2026 Dominik Zogg

[1]: https://packagist.org/packages/chubbyphp/chubbyphp-static-file

[2]: https://packagist.org/packages/psr/http-factory
[3]: https://packagist.org/packages/psr/http-message
[4]: https://packagist.org/packages/psr/http-server-handler
[5]: https://packagist.org/packages/psr/http-server-middleware
