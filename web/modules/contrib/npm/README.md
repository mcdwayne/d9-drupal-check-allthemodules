Provides tools to interact with NPM.

## Usage

```php
  /** @var Drupal\npm\Plugin\NpmExecutableInterface $npmExecutable */
  $npmExecutable = \Drupal::service('plugin.manager.npm_executable')->getExecutable();
```

## Details

Right now `yarn` is the only supported executable. Others can be added by implementing `NpmExecutable` Plugins. Each plugin has a weight that determines its priority and an `isAvailable` method that tells if it's operational (e.g. yarn is installed). The plugin manager's `getExecutable` method returns the first available executable.
- [The plugin interface](https://github.com/drupal-webpack/npm/blob/8.x-1.x/src/Plugin/NpmExecutableInterface.php)
- [Example implementation](https://github.com/drupal-webpack/npm/blob/8.x-1.x/src/Plugin/NpmExecutable/Yarn.php)

## Related modules
- [Webpack](https://drupal.org/project/webpack)
