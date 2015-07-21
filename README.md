# clue/confgen [![Build Status](https://travis-ci.org/clue/php-confgen.svg?branch=master)](https://travis-ci.org/clue/php-confgen)

Configuration file generator (confgen) –
an easy way to take a *Twig template* and an arbitrary input data structure to
generate structured (configuration) files on the fly. 

> Note: This project is in beta stage! Feel free to report any issues you encounter.

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
{% for interface in data.interfaces %}
auto {{ interface.name }}
    address {{ interface.address }}
{% endfor %}
```

### Meta variables

The template files can optionally start with the meta-data in the form of a YAML front matter.
This syntax is quite simple and is pretty common for template processors and
static site generators such as [Jekyll](http://jekyllrb.com/docs/frontmatter/).

Documented variables:

* `target` target path to write the resulting file to
* `chmod` file mode of the resulting file
* `reload` command to execute after writing the resulting file
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

See [configuration schema](res/schema-confgen.json) for more details.

## Install

The recommended way to install this library is [through composer](http://getcomposer.org). [New to composer?](http://getcomposer.org/doc/00-intro.md)

```JSON
{
    "require": {
        "clue/confgen": "~0.2.0"
    }
}
```

## License

MIT
