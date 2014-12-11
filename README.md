# GitValidate [![Build Status](https://travis-ci.org/MisumiRize/php-git-validate.svg)](https://travis-ci.org/MisumiRize/php-git-validate)

GitValidate is a CLI tool for Git hook validation.

## Requirement

* PHP >= 5.4
* symfony/console
* symfony/process
* phine/path

## Installation

### Symlink

```shell
$ ln -s /path/to/bin/validate /path/to/repo/.git/hooks/pre-commit
```

### .validate.json

```json
{
    "pre-commit": "test",
    "scripts": {
        "test": "phpunit"
    }
}
```
