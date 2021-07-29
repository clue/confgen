# clue/confgen

[![Build Status](https://travis-ci.org/clue/confgen.svg?branch=master)](https://travis-ci.org/clue/confgen)
[![installs on Packagist](https://img.shields.io/packagist/dt/clue/confgen?color=blue&label=install%20on%20Packagist)](https://packagist.org/packages/clue/confgen)

Configuration file generator (confgen) –
an easy way to generate structured (configuration) files on the fly by
processing a *Twig template* and an arbitrary input data structure.

**Table of contents**

* [Input data](#input-data)
* [Templates](#templates)
* [Meta variables](#meta-variables)
* [Configuration](#configuration)
* [Bin Usage](#bin-usage)
* [Lib Usage](#lib-usage)
  * [Factory](#factory)
    * [Twig_Environment](#twig_environment)
    * [createConfgen()](#createconfgen)
  * [Confgen](#confgen)
    * [processTemplate()](#processtemplate)
    * [processDefinition()](#processdefinition)
* [Install](#install)
* [Tests](#tests)
* [License](#license)

## Input data

This project is all about transforming *your input data* structure.

As such, it makes no assumptions as to what kind of input data you're
dealing with, as long as it can be expressed in a simple JSON structure.
This project focuses on JSON input data for a few key reasons:

* Arbitrary data structure
  * Can contain pretty much any data structure
  * Simple, sane, strict data types
  * Flat or deeply nested structures
  * Schemaless by default – but offers options for using schema
* Ease of consuming (simple to read)
  * For both humans and machines alike
  * Easy to reason about
  * Maps well into dotted notation used in template files
* Ease of producing (simple to write)
  * Simple to convert into from many other common formats, such as YAML, XML, CSV, INI etc.
  * Very easy to write in PHP and many other languages
* Widespread use

Chances are, your input data *might* already be in a JSON file.
If it's not, then it's very easy to either convert with one of the many existing tools
or libraries or use some code similar to the following example:

```php
// $data = loadFromYaml('input.yml');
// $data = parseFromIni('input.ini');
$data = fetchFromDatabase();
file_put_contents('data.json', json_encode($data));
```

The structure of your input data file is entirely left up to you.
This library allows you to use any arbitrary input data structure.
For the following examples, this document assumes the following
(totally arbitrary) input data structure:

```json
{
    "timeout": 120,
    "interfaces": [
        {
            "name": "eth0",
            "address": "192.168.1.1"
        }
    ]
}
```

## Templates

Each (configuration) template file is essentially a plaintext
(output) configuration file with some placeholders.

The template file uses the *Twig template language* and can hence
take full advantage of its variable substitution and advanced
template control logic.

In its most simple form, an arbitrary template would
look something like this:

```
timeout = {{ data.timeout }}
{% for interface in data.interfaces %}
auto {{ interface.name }}
    address {{ interface.address }}
{% endfor %}
```

The input variables will be accessible under the `data` key.

You can generate the output (configuration) file by [invoking confgen](#bin-usage) like this:

```bash
$ confgen -t template.twig -d data.json
```

In this example, it will write the resulting file to the template file name without extension (i.e. `template`).

With the above example template and input data,
the resulting output (configuration) file will look something like this:

```
timeout = 120
auto eth0
    address 192.168.1.1
```

## Meta variables

Optionally, you can prefix the template file contents with the meta-data in the form of a YAML front matter.
This syntax is quite simple and is pretty common for template processors and
static site generators such as [Jekyll](http://jekyllrb.com/docs/frontmatter/).

This means that if you want to include *meta-data* variables, then
each section starts with a three-hyphen divider (`---`), so that a full file would
look something like this:

```
---
target: /etc/network/interfaces
chmod: 644
reload: /etc/init.d/networking reload
---
timeout = {{ data.timeout }}
{% for interface in data.interfaces %}
auto {{ interface.name }}
    address {{ interface.address }}
{% endfor %}
```

Documented variables:

* `target` target path to write the resulting file to.
  Can be an abolute or relative path that will be resolved relative to the directory confgen is called in (i.e. not relative to this template file).
* `chmod` file permissions (decimal) for the target file
* `reload` command to execute after writing the target file
* `description` human readable description

You can also pass arbitrary custom meta-data.
See [meta-data schema](res/schema-template.json) for more details.

The meta variables will be accessible under the `meta` key in the Twig template.
If no *meta-data* variables are present, then this key defaults to an empty array.

You can generate the output (configuration) file by [invoking confgen](#bin-usage) like this:

```bash
$ confgen -t template.twig -d data.json
```

If the [template meta-data](#meta-variables) contains a `target` key,
it will write the resulting file to this location.

In the above example, this means the following actions will be performed:

* Write output (configuration) file to `/etc/network/interfaces`
* Set file permissions to `0644`
* Execute the reload script `/etc/init.d/network restart`

Sometimes it is useful to skip the execution of the scripts/commands defined by the meta variable `reload`.
To do so you can use the optional parameter `--no-scripts` like this:

```bash
$ confgen --no-scripts -t template.twig -d data.json
```

## Configuration

You can either parse/process individual template files or use a configuration
definition that allows you to process a number of files in one go.

In its most simple form, a JSON configuration structure looks like this:

```json
{
    "templates": "example/*.twig"
}
```

Documented variables:

* `templates`
  Can be an absolute or relative path that will be resolved relative to this definition (i.e. not necessarily the $PWD)

See [configuration schema](res/schema-confgen.json) for more details.

You can generate the output (configuration) files by [invoking confgen](#bin-usage) like this:

```bash
$ confgen [--no-scripts] -c confgen.json -d data.json
```

This works similar to invoking with individual [template files](#templates).

## Bin Usage

Once [installed](#install), you can use this tool as a bin(ary) executable.

Some usage examples are given above.

If you want to see the usage help,
simply invoke its help by calling like this:

```bash
$ confgen
```

If you have installed this via `$ composer require`, then you may have to
invoke it like this:

```bash
$ ./vendor/bin/confgen
```

## Lib Usage

See the above section for [bin usage](#bin-usage) which is usually easier to get started.

If you want to integrate this into another tool, you may also use this project as a lib(rary).
The same also applies if you want to use custom twig extensions, functions or filters.

### Factory

The `Factory` class is a helper class that can be used to *easily* create
a new `Confgen` instance.

```php
$factory = new Factory();
```

#### Twig_Environment

Internally, the `Factory` will create a `Twig_Environment` instance that
will be used to process the template files.

You may want to explicitly pass an instance if you want to use any of the following:

* custom twig extensions
* custom twig functions
* custom twig filters

```php
$twig = new Twig_Environment();
$twig->addFilter(new Twig_SimpleFilter('backwards', function ($value) {
    return strrev($value);
});

$factory = new Factory($twig);
```

#### createConfgen()

The `createConfgen()` method can be used to create a new `Confgen` instance.
Usually, there should be no need to call this more than once.

```php
$confgen = $factory->createConfgen();
```

### Confgen

The `Confgen` class is responsible for processing the templates
(*this is where the magic happens*).

#### processTemplate

The `processTemplate($templateFile, $dataFile)` method can be used to
generate a output (configuration) file from the given [template file](#templates).

```php
$confgen->processTemplate('template.twig', 'data.json');
```

See also [templates section](#templates) above for more details on the
The [input data](#input-data).

#### processDefinition

The `processDefinition($definitionFile, $dataFile)` method can be used to
generate any number of output (configuration) files from the given [configuration file](#configuration).

```php
$confgen->processDefinition('confgen.json', 'data.json');
```

See also [configuration section](#configuration) above for more details.

## Install

You can simply download a pre-compiled and ready-to-use version as a Phar
to any directory.
Simply download the latest `confgen.phar` file from our
[releases page](https://github.com/clue/confgen/releases):

[Latest release](https://github.com/clue/confgen/releases/latest)

That's it already. You can now verify everything works by running this:

```bash
$ cd ~/Downloads
$ php confgen.phar -h

$ chmod +x confgen.phar
$ sudo mv confgen.phar /usr/local/bin/confgen
```

Alternatively, you can also use this project as a library to integrate this into
an existing application.
The recommended way to install this library is [through Composer](https://getcomposer.org).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

```bash
$ composer require clue/confgen:^0.6
```

This project aims to run on any platform and thus does not require any PHP
extensions and supports running on legacy PHP 5.3 through current PHP 7+.
It's *highly recommended to use PHP 7+* for this project.

> If you want to create the above `confgen.phar` locally, you have to clone
  this repository and run `composer build`.

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](http://getcomposer.org):

```bash
$ composer install
```

To run the test suite, go to the project root and run:

```bash
$ php vendor/bin/phpunit
```

## License

MIT
