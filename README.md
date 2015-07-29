# clue/confgen [![Build Status](https://travis-ci.org/clue/php-confgen.svg?branch=master)](https://travis-ci.org/clue/php-confgen)

Configuration file generator (confgen) –
an easy way to take a *Twig template* and an arbitrary input data structure to
generate structured (configuration) files on the fly. 

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

Each (configuration) template file is broken into two parts:

* The optional, leading YAML front matter (or *meta-data* variables)
* And the actual Twig template contents

In its most simple form, a template without the optional YAML front matter would
look something like this:

```
timeout = {{ data.timeout }}
{% for interface in data.interfaces %}
auto {{ interface.name }}
    address {{ interface.address }}
{% endfor %}
```

If you also want to include *meta-data* variables, then
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

The individual sections are described in more detail in the following sections.

You can generate the output (configuration) file by [invoking confgen](#bin-usage) like this:

```bash
$ confgen -t template.twig -d data.json
```

If the [template meta-data](#meta-variables) contains a `target` key,
it will write the resulting file to this location.
Otherwise it will write to the template file name without extension (i.e. `template`).

With the above example template and input data,
the resulting output (configuration) file will look something like this:

```
timeout = 120
auto eth0
    address 192.168.1.1
```

### Meta variables

The template files can optionally start with the meta-data in the form of a YAML front matter.
This syntax is quite simple and is pretty common for template processors and
static site generators such as [Jekyll](http://jekyllrb.com/docs/frontmatter/).

Documented variables:

* `target` target path to write the resulting file to.
  Can be an abolute or relative path that will be resolved relative to the directory confgen is called in (i.e. not relative to this template file).
* `chmod` file permissions (decimal) for the target file
* `reload` command to execute after writing the target file
* `description` human readable description

You can also pass arbitrary custom meta-data.
See [meta-data schema](res/schema-template.json) for more details.

The meta variables will be accessible under the `meta` key in the Twig template.

### Template contents

Can contain any *Twig template*.

The input variables will be accessible under the `data` key.

The meta variables will be accessible under the `meta` key.
If no *meta-data* variables are present, then this key defaults to an empty array.

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
$ confgen -c confgen.json -d data.json
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

The recommended way to install this library is [through composer](http://getcomposer.org). [New to composer?](http://getcomposer.org/doc/00-intro.md)

```JSON
{
    "require": {
        "clue/confgen": "~0.5.0"
    }
}
```

## License

MIT
