# Utilities for my php-json-* packages

[![Linux Build Status](https://travis-ci.org/WyriHaximus/php-json-utilities.png)](https://travis-ci.org/WyriHaximus/php-json-utilities)
[![Windows Build status](https://ci.appveyor.com/api/projects/status/1sfdh9g2pvbuw4pp?svg=true)](https://ci.appveyor.com/project/WyriHaximus/php-json-utilities)
[![Latest Stable Version](https://poser.pugx.org/WyriHaximus/json-utilities/v/stable.png)](https://packagist.org/packages/WyriHaximus/json-utilities)
[![Total Downloads](https://poser.pugx.org/WyriHaximus/json-utilities/downloads.png)](https://packagist.org/packages/WyriHaximus/json-utilities)
[![Code Coverage](https://scrutinizer-ci.com/g/WyriHaximus/php-json-utilities/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/WyriHaximus/php-json-utilities/?branch=master)
[![License](https://poser.pugx.org/WyriHaximus/json-utilities/license.png)](https://packagist.org/packages/wyrihaximus/json-utilities)
[![PHP 7 ready](http://php7ready.timesplinter.ch/WyriHaximus/php-json-utilities/badge.svg)](https://travis-ci.org/WyriHaximus/php-json-utilities)

### Installation ###

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `~`.

```
composer require wyrihaximus/json-utilities 
```

# Available functions

### validate_array

Validates an array by checking if the list of required keys all can be found in the passed array. On success it will 
return `true`, when it comes across a key it can find in the array it will return `false`. How ever then passed an 
exception it will create that exception by passing the array as first argument and the missing key as second argument 
to the constructor.

```php
$array = ['key', 'another_key', 'required_key']; //

$requiredKeys = ['required_key'];

validate_array($array, $requiredKeys, ExceptionToBeTrownWhenAKeyIsMissing::class);
```

## Contributing ##

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License ##

Copyright 2018 [Cees-Jan Kiewiet](http://wyrihaximus.net/)

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
