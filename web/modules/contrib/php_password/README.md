
## CONTENTS OF THIS FILE


 * Introduction
 * Installation
 * Configuration


## INTRODUCTION


Current Maintainer: James Gilliland <neclimdul@gmail.com>

Replace Drupal's in PHP hashing algorythm with php's native hashing algorythm.

## INSTALLATION


The php_password module will take care of replacing Drupal's password manager
for you so installation is as simple as installing any other Drupal module.

## CONFIGURATION


This module doesn't have a lot of configuration needed out of the box. However
you may choose to tweak some of the parameters used to hash new passwords.

### Hash Cost
Hash cost can be configured though a container parameter.

You can add the following snippet to your sites `services.yml` file to modify
the cost:

```
parameters:
  password_hash_cost: 8

```

Additionally a Drush command is provided to help you calculate an ideal value
with between 100-500ms runtime on your specific hardware.

```
$ drush password-hash-cost
```

### Hash algorithm
As of php 7.2 there are 2 password hashing mechanisms however php_password only
supports  using PHP's default(bcrypt) hash algorithm at this time.