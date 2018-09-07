TinyButXtreme
=============
[![Build Status](https://travis-ci.org/darkain/TinyButXtreme.svg?branch=master)](https://travis-ci.org/darkain/TinyButXtreme)




TinyButXtreme (TBX) is a fork of
[TinyButStrong](https://github.com/Skrol29/tinybutstrong) (TBS) with countless
changes which make it incompatible with the original library. Documented below
are the major changes that have been made, but know that this is not a complete
list due to the active development nature of TBX.




## Overview
The original reason for this fork is due to TBS inconsistent handling of Unix
Timestamps. For example, the Unix Timestamp 20140416 in TBS will be read as a
date of 2014-04-16, whereas the Unix Timestamp of 20142000 will be read as
1970-08-22 @ 03:00:00. Within TBX, both of these are handled as the latter case,
as no assumptions are made about intended usage of strictly numerical values.
From there, development continued to simplify the API, add performance
optimizations, remove unused code features, and organize the code base.




## Features Removed

* PHP 5.4 is now the minimum version. All support for previous PHP versions
removed. PHP 7.x and HHVM are fully supported.
* Plugin support entirely removed.
* Microsoft Office support entirely removed.
* Most Data Sources removed.
* `onformat=` removed.
* `[var]` global variables removed.




## API Changes

* All `TBS_*` constants renamed to `TBX_*`.
* Class `clsTinyButStrong` renamed to `tbx`.
* All `tbx` class constructor optional parameters removed.
* Rendering templates no longer exits application.
* Method `meth_Misc_Alert` has been replaced by Exception `tbxException`.
* Numerical values passed into Date functions are always treated as Unix
Timestamps.
* Method `MergeField` replaced with method `field`.
* Method `MergeBlock` replaced with method `block`.
* Method `reset` added to reset all template states.
* Method `fields` added to iterate through an array and call `field` on each
item.
* Method `repeat` added to simplify the process of access `_P1`.
* Method `merge` added to iterate through an array and call `field` or `block`
depending upon data type of each item.
* Method `LoadTemplate` replaced with method `load`.
* `$tbs->Source = $value` replaced with method `loadString($value)`.
* `$value = $tbs->Source` replaced with method `$value = (string) $tbx`
* Method `loadArray` added to load multiple files into a single template.
* Method `Show` replaced with method `render`.
* Method `renderToString` added to output final template to a string instead of
stdout/browser.




## Template Syntax Changes

* `ope=[STRING FUNCTION]` has been replaced with `f=[STRING FUNCTION]`.
* `ope=[MATH FUNCTION]` has been extended to include more functions.
* `ope=` and `f=` may be used together on the same value.
* `frm=` date formatting replaced by `date=`.
* Date formatting now uses PHP's date string syntax instead of a proprietary
syntax. http://php.net/manual/en/function.date.php
* `strconv=` is now an alias of `safe=`.
* `ifempty` no longer issues a warning/error for unset data source keys.
* `magnet` no longer issues a warning/error for unset data source keys.




## Data Sources

TBS includes a variety of potential data sources to feed information into
templates. The majority of these have all been removed. TBX on the other hand
supports a very small set of data sources which are listed below.

The main difference is that all database engines supported have been removed,
and instead replaced by support for the
[PHP Universal Database Library (PUDL)](https://github.com/darkain/pudl).
This greatly simplifies access to a number of database engines, while also
allowing support for new engines without modifying TBX at all.

Additionally, numerous bugs involving PHP objects and Iterator have been
fixed.

* [PHP literals](http://php.net/manual/en/language.types.intro.php)
* [PHP arrays](http://php.net/manual/en/language.types.array.php)
* [PHP objects](http://php.net/manual/en/language.types.object.php)
* PHP objects which implement the [Iterator interface](http://php.net/manual/en/class.iterator.php)
* [PUDL pudlResult](https://github.com/darkain/pudl/blob/master/pudlResult.php)
* [PUDL pudlCollection](https://github.com/darkain/pudl/blob/master/pudlCollection.php)




## `ope=` Changes

* `ope=list` removed
* `ope=minv` removed
* `ope=attbool` removed
* `ope=utf8` removed
* `ope=upper` removed
* `ope=lower` removed
* `ope=upper1` removed
* `ope=upperw` removed
* `ope=max:VALUE` unchanged (max between current vs value)
* `ope=mod:VALUE` unchanged (current % value)
* `ope=add:VALUE` unchanged (current + value)
* `ope=mul:VALUE` unchanged (current * value)
* `ope=div:VALUE` unchanged (current / value)
* `ope=mok:VALUE` unchanged (magnet??)
* `ope=mko:VALUE` unchanged (magnet??)
* `ope=nif:VALUE` unchanged (if current == value, set current='')
* `ope=msk:VALUE` unchanged (replace '\*' in current with value)
* `ope=sub:VALUE` added (current - value)
* `ope=mdx:VALUE` added (value % current)
* `ope=adx:VALUE` added (value + current)
* `ope=sbx:VALUE` added (value - current)
* `ope=mlx:VALUE` added (value * current)
* `ope=dvx:VALUE` added (value / current)




## `function=` Added

Execute one or more PHP functions on a given value. Function names are separated
by `,` (commas). Any function not supported by the TBX library directly will be
forwarded to the `_customFormat($value, $function)` method. Inherit from the
`tbx` class and overwrite this method to do application specific function
processing of template parameters.

`f=` and `convert=` are aliases of `function=`.

Template Examples:
```
[item;f=hex] - convert [item] into a hex value
[item;f=upper] - convert [item] into upper case character
[item;f=hex,upper] - convert [item] into a hex value with upper case characters
[item;f=hex,lower] - convert [item] into a hex value with lower case characters
```

Custom Format Example:
```
Template:
[item;f=awesome]

PHP:
class myAwesomeClass extends tbx {
	function _customFormat(&$value, $function) {
		//Replace the value with 'AWESOME!'
		if ($function === 'awesome') $value = 'AWESOME!';
	}
}
```

Complete list of supported functions:
https://github.com/darkain/TinyButXtreme/blob/master/tbx_function.inc.php

PHP functions supported
* [addslashes](http://php.net/manual/en/function.addslashes.php)
* [bin2hex](http://php.net/manual/en/function.bin2hex.php)
* [chr](http://php.net/manual/en/function.chr.php)
* [chunk_split](http://php.net/manual/en/function.chunk-split.php)
* [hebrev](http://php.net/manual/en/function.hebrev.php)
* [hebrevc](http://php.net/manual/en/function.hebrevc.php)
* [hex2bin](http://php.net/manual/en/function.hex2bin.php)
* [html_entity_decode](http://php.net/manual/en/function.html-entity-decode.php)
* [htmlentities](http://php.net/manual/en/function.htmlentities.php)
* [htmlspecialchars](http://php.net/manual/en/function.htmlspecialchars.php)
* [lcfirst](http://php.net/manual/en/function.lcfirst.php)
* [ltrim](http://php.net/manual/en/function.ltrim.php)
* [md5](http://php.net/manual/en/function.md5.php)
* [metaphone](http://php.net/manual/en/function.metaphone.php)
* [nl2br](http://php.net/manual/en/function.nl2br.php)
* [number_format](http://php.net/manual/en/function.number-format.php)
* [ord](http://php.net/manual/en/function.ord.php)
* [rawurlencode](http://php.net/manual/en/function.rawurlencode.php)
* [rtrim](http://php.net/manual/en/function.rtrim.php)
* [sha1](http://php.net/manual/en/function.sha1.php)
* [soundex](http://php.net/manual/en/function.soundex.php)
* [strip_tags](http://php.net/manual/en/function.strip-tags.php)
* [stripcslashes](http://php.net/manual/en/function.stripcslashes.php)
* [stripslashes](http://php.net/manual/en/function.stripslashes.php)
* [strlen](http://php.net/manual/en/function.strlen.php)
* [strrev](http://php.net/manual/en/function.strrev.php)
* [strtolower](http://php.net/manual/en/function.strtolower.php)
* [strtoupper](http://php.net/manual/en/function.strtoupper.php)
* [trim](http://php.net/manual/en/function.trim.php)
* [ucfirst](http://php.net/manual/en/function.ucfirst.php)
* [ucwords](http://php.net/manual/en/function.ucwords.php)
* [urlencode](http://php.net/manual/en/function.urlencode.php)
* [uudecode](http://php.net/manual/en/function.convert-uudecode.php)
* [uuencode](http://php.net/manual/en/function.convert-uuencode.php)
* [wordwrap](http://php.net/manual/en/function.wordwrap.php)


Additional functions and aliases supported
* `bin` - alias of `hex2bin`
* `crc32` - shortcut for `hash('crc32', $value)`
* `crc32b` - shortcut for `hash('crc32b', $value)`
* `hex` - alias of `bin2hex`
* `html` - alias of `htmlspecialchars`
* `lower` - alias of `strtolower`
* `md2` - shortcut for `hash('md2', $value)`
* `md4` - shortcut for `hash('md4', $value)`
* `phone` - cleans up a phone number (several formats supported)
* `rawid` - shortcut for `strtolower(rawurlencode(rtrim(substr($value, 0, 20))))`
* `rawname` - shortcut for `strtolower(rawurlencode($value))`
* `sha256` - shortcut for `hash('sha256', $value)`
* `sha384` - shortcut for `hash('sha384', $value)`
* `sha512` - shortcut for `hash('sha512', $value)`
* `title` - shortcut for `ucwords(strtolower($value))` with special handling for certain words
* `unhex` - alias of `hex2bin`
* `upper` - alias of `strtoupper`
* `url` - alias of `urlencode`
* `urlid` - shortcut for `strtolower(urlencode(rtrim(substr($value, 0, 20))))`
* `urlname` - shortcut for `strtolower(urlencode($value))`
