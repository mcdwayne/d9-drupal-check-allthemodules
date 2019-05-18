# Performance Budget

Create and configure performance budgets using various plugins and providers.

## Instructions

Unpack in the *modules* folder (currently in the root of your Drupal 8
  installation) and enable in `/admin/modules`.

Then, visit `/admin/reports/performance-budget` to create and configure
performance budgets for your needs.

## Contributing

Please follow the standards as explained in the Examples for Developers module:

http://cgit.drupalcode.org/examples/tree/STANDARDS.md

### PHPCS using Drupal standard

```shell
# From drupal root - please adjust to your environment
$ vendor/bin/phpcs -p -s --standard=Drupal modules
..

Time: 97ms; Memory: 8Mb
```

### Tests

```shell
# From drupal root - please adjust to your environment
$ php core/scripts/run-tests.sh --url http://127.0.0.1:8888 --color --module performance_budget
```

## Helpful resources

https://www.google.com/search?q=performance+budget
http://bradfrost.com/blog/post/performance-budget-builder/
https://www.keycdn.com/blog/web-performance-budget/
