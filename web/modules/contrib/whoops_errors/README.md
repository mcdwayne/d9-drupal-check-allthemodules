# Whoops!

Drupal 8 module that enables the Whoops! error handler when encountering catchable errors.

# Dependencies

- [flip/whoops](https://github.com/filp/whoops)

# Installation

```
composer require drupal/whoops_errors
```

# Usage

The module will register the error handler whenever a catchable error is thrown so it is best not to use in production.

There is no additional configuration required.
