# clue/confgen [![Build Status](https://travis-ci.org/clue/php-confgen.svg?branch=master)](https://travis-ci.org/clue/php-confgen)

Configuration file generator (confgen) –
an easy way to take a *Twig template* and an arbitrary input data structure to
generate structured (configuration) files on the fly. 

> Note: This project is in early alpha stage! Feel free to report any issues you encounter.

## Templates

Each (configuration) template file is broken into two parts:

* The leading YAML front matter (or *variables* or *meta data*)
* And the actual Twig template contents

Each section starts with a three-hyphen divider (`---`), so that a full file would
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

The template files start with the meta-data in the form of a YAML front matter.
This syntax is quite simple and is pretty common for template processors and
static site generators such as [Jekyll](http://jekyllrb.com/docs/frontmatter/).

Documented variables:

* `target` target path to write the resulting file to
* `chmod` file mode of the resulting file
* `reload` command to execute after writing the resulting file
* `description` human readable description

You can also pass arbitrary custom meta-data.

The meta variables will be accessible under the `meta` key in the Twig template.

### Template contents

Can contain any *Twig template*.

The input variables will be accessible under the `data` key.

The meta variables will be accessible under the `meta` key.

## Install

The recommended way to install this library is [through composer](http://getcomposer.org). [New to composer?](http://getcomposer.org/doc/00-intro.md)

```JSON
{
    "require": {
        "clue/confgen": "dev-master"
    }
}
```

## License

MIT
