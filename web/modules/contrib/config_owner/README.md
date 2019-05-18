# Config Owner

The Config Owner module provides the ability for modules to control the configuration they ship with. That means preventing sites that use it from
exporting/importing changes to these configurations.

## How to use

The module works by exposing a YML based plugin type called Owned Config. Any module that wants to "own" some configuration, needs to do two things:

1. Export the configuration they want to own (see below for the Drush commands).
2. Create an `owned_config` plugin that references these configs and/or keys inside them for granularity (these plugins go inside a file named `module_name.owned_config.yml`). See the `config_owner_test.owned_config.yml` file as an example.

In the plugin, the "owned" configuration is referenced under three relevant sections:

* `install` -> configuration the module ships with by in the `config/install` folder.
* `optional` -> optional configuration the module ships with in the `config/optional` folder.
* `owned` -> specific configuration that the module does not ship with (gets already installed by another module for example) but wants to own. See the Drush command for how to export such configuration into the `config/owned` folder.

These keys map to the location inside the module's `config` directory of the module.

## Notation

For each configuration (under any of the above sections), you can:

* Specify to own the entire config object:
```
module_name.settings: ~
```

* Specify to own multiple config objects named similarly, using a wildcard:
```
module_name.settings.*: ~
```

* Specify to own only certain keys inside a config:
```
module_name.settings: ~
  - key_one
  - key_two
```

In all these cases, third party settings will not be owned because their purpose is to actually allow other modules to enhance a given configuration. If, however, some third party settings need to be owned, they need to be expressly specified. In case the entire config is owned, the notation looks like this:
```
module_name.settings:
  - "*"
  - third_party_settings.module_name
```

## Drush commands

The module exposes 2 Drush commands.

### Exporting config

```
drush config-owner:export 
```

This command takes two parameters (which can also be derived interactively if omitted):

* The module name
* The config name

The command is used as a helper to export a given configuration object from the active storage to the module's `config/owned` folder. 

The `config/owned` folder is used to store configuration objects that the module "owns" but that it cannot ship with (it can already exist in the active storage).

After exporting the configuration file, its name needs to be referenced in the Owned Config plugin under the `owned` key.

### Importing config

```
drush config-owner:import 
```

Owned config no longer goes through the normal configuration sync process. This means that changes to the owned configuration in the Sync (staging) storage will not be imported during the sync. 

Using this command, you can import the owned configuration provided by all the modules into the active storage. So it should go hand in hand with the core `drush config-import`.


## Development setup

You can build the test site by running the following steps.

* Install all the composer dependencies:

```
$ composer install
```

* Customize build settings by copying `runner.yml.dist` to `runner.yml` and
changing relevant values, like your database credentials.


* Install test site by running:

```
$ ./vendor/bin/run drupal:site-install
```

Your test site will be available at `./build`.

### Using Docker Compose

Alternatively you can build a test site using Docker and Docker-compose with the provided configuration.

Requirements:

- [Docker](https://www.docker.com/get-docker)
- [Docker-compose](https://docs.docker.com/compose/)

Run:

```
$ docker-compose up -d
```

Then:

```
$ docker-compose exec web composer install
$ docker-compose exec web ./vendor/bin/run drupal:site-setup
$ docker-compose exec web ./vendor/bin/run drupal:site-install
```

Your test site will be available at [http://localhost:8080/build](http://localhost:8080/build).

To run the grumphp test:

```
$ docker-compose exec web ./vendor/bin/grumphp run
```

To run the phpunit test:

```
$ docker-compose exec web ./vendor/bin/phpunit
```