# Developing on Disrupt Tools

* Issues should be filed at
https://www.drupal.org/project/issues/disrupt_tools
* Pull requests can be made against
https://github.com/antistatique/drupal-disrupt-tools/pulls

## 📦 Repositories

Drupal repo

  ```
  git remote add drupal git@git.drupal.org:project/disrupt_tools.git
  ```

Github repo
  ```
  git remote add github https://github.com/antistatique/drupal-disrupt-tools.git
  ```

## 🔧 Prerequisites

First of all, you need to have the following tools installed globally
on your environment:

  * drush
  * Latest dev release of Drupal 8.x.

## 🏆 Tests

Disrupt Tools use UnitTest.

  ```bash
  $ cd core
  $ cp phpunit.xml.dist phpunit.xml
  ```

Run the unit tests:

  ```bash
  # You must be on the drupal-root folder - usually /web.
  $ cd web
  $ ../vendor/bin/phpunit -c core \
  --group disrupt_tools
  ```

Debug using

  ```bash
  # You must be on the drupal-root folder - usually /web.
  $ cd web
  $ ../vendor/bin/phpunit -c core \
  --group disrupt_tools \
  --printer="\Drupal\Tests\Listeners\HtmlOutputPrinter" --stop-on-error
  ```

## 🚔 Check Drupal coding standards & Drupal best practices

You need to run composer before using PHPCS. Then register the Drupal
and DrupalPractice Standard with PHPCS:

  ```bash
  $ ./vendor/bin/phpcs --config-set installed_paths \
  `pwd`/vendor/drupal/coder/coder_sniffer
  ```

### Command Line Usage

Check Drupal coding standards:

  ```bash
  ./vendor/bin/phpcs --standard=Drupal --colors \
  --extensions=php,module,inc,install,test,profile,theme,css,info,md \
  --ignore=*/vendor/*,*/node_modules/* ./
  ```

Check Drupal best practices:

  ```bash
  ./vendor/bin/phpcs --standard=DrupalPractice --colors \
  --extensions=php,module,inc,install,test,profile,theme,css,info,md \
  --ignore=*/vendor/*,*/node_modules/* ./
  ```

Automatically fix coding standards

  ```bash
  ./vendor/bin/phpcbf --standard=Drupal --colors \
  --extensions=php,module,inc,install,test,profile,theme,css,info \
  --ignore=*/vendor/*,*/node_modules/* ./
  ```

### Improve global code quality using PHPCPD & PHPMD

Add requirements if necessary using `composer`:

  ```bash
  composer require --dev 'phpmd/phpmd:^2.6' 'sebastian/phpcpd:^3.0'
  ```

Detect overcomplicated expressions & Unused parameters, methods, properties

  ```bash
  ./vendor/bin/phpmd ./web/modules/custom text ./phpmd.xml
  ```

Copy/Paste Detector

  ```bash
  ./vendor/bin/phpcpd ./web/modules/custom
  ```

### Enforce code standards with git hooks

Maintaining code quality by adding the custom post-commit hook to yours.

  ```bash
  cat ./scripts/hooks/post-commit >> ./.git/hooks/post-commit
  ```
