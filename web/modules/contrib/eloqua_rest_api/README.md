Eloqua REST API
================

This is a Drupal extension that integrates [Elomentary][]--a PHP library that
facilitates communication with Eloqua's REST API--with Drupal. It does nothing
on its own, other than make Elomentary available to Drupal and authenticate API
calls with configurable credentials.

Install this module if you're using another module that depends on it. You might
also want to use this module if you're a developer looking to more deeply
integrate Drupal and Eloqua.


## Installation

This module relies on [Composer][] to load in its dependencies. Currently, two
methods of installation are supported. Details follow:

#### Via Drupal Composer

For brand new projects, you may wish to install Drupal itself via Composer.

1. If you haven't already, initialize your Drupal project into `some-directory`:
   `composer create-project drupal-composer/drupal-project:8.x-dev some-directory --stability dev --no-interaction`
2. In the root of your project (`cd some-directory`), require this module via
   Drupal packagist: `composer require drupal/eloqua_rest_api:8.*`
3. Then enable this module: `drush en eloqua_rest_api`

More information on [installing Drupal via Composer][] is available on
GitHub.

#### Via Composer Manager

If you're adding Eloqua REST API support to an existing website, you may wish to
install this module the "normal" Drupal way, along side [Composer Manager][].
Instructions for this method follow:

1. Install this module and and [Composer Manager][], via drush:
  `drush dl eloqua_rest_api composer_manager`
2. Enable Composer Manager: `drush en composer_manager`
3. Then enable this module: `drush en eloqua_rest_api`
4. Composer Manager may automatically download and enable requisite PHP
   libraries, but if not, run `drush composer-manager install` or
   `drush composer-manager update`.

More information on [installing and using Composer Manager][] is available on
GitHub.


## Configuration

1. Ensure your user has the permission `administer eloqua rest api`.
2. Navigate to `admin/config/services/eloqua` to enter Eloqua credentials. It's
   recommended that you use admin credentials to your Eloqua Instance, otherwise
   your password may expire and break integrations.


## Developing with this module

First, be sure in your custom / contributed module to add a dependency on this
module in your module.info.yml file:

```yml
dependencies:
  - eloqua_rest_api
```

You can get an authenticated Elomentary client in a number of ways. In your
procedural or hook-based code, you can use the global `eloqua_rest_api_client()`
function to return a client. For example:

```php
$client = eloqua_rest_api_client();
$contact = $client->api('contact')->show(123);
```

This module also provides an Eloqua client factory that can be accessed via the
global `Drupal` object or injected as a service into any class:

```php
// Pull the factory service from Drupal and return a client.
$client = \Drupal::service('eloqua.client_factory')->get();
$contact = $client->api('contact')->search('*@example.com');
```

```php
use Drupal\eloqua_rest_api\Factory\ClientFactory;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

MyClass implements ContainerInjectionInterface {

  /**
   * @var ClientFactory
   */
  protected $clientFactory;

  public function __construct(ClientFactory $clientFactory) {
    $this->clientFactory = $clientFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eloqua.client_factory')
    );
  }

  /**
   * Demonstrating use of the factory.
   */
  public function getContact($id) {
    $client = $this->clientFactory->get();
    return $client->api('contact')->show($id);
  }

}
```

Finally, if you need to globally modify the Elomentary client, you can implement
`hook_eloqua_rest_api_client_alter()`. See [eloqua_rest_api.api.php]() for more
info.

For further details on how to use Elomentary, see
[Elomentary usage documentation]() on GitHub.

Note also that this module references an early version of Elomentary that only
implements a subset of Eloqua's API. Work on [full integration is ongoing]()
and contributions are welcomed and encouraged!

[Elomentary]: https://github.com/tableau-mkt/elomentary
[Composer]: https://getcomposer.org/
[installing Drupal via Composer]: https://github.com/drupal-composer/drupal-project/tree/8.x
[Composer Manager]: https://www.drupal.org/project/composer_manager
[installing and using Composer Manager]: https://github.com/cpliakas/composer-manager-docs/blob/master/README.md#installation
[eloqua_rest_api.api.php]: eloqua_rest_api.api.php
[Elomentary usage documentation]: https://github.com/tableau-mkt/elomentary/blob/0.1/doc/index.md
[full integration is ongoing]: https://github.com/tableau-mkt/elomentary/issues
