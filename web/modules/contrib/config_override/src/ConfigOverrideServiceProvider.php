<?php

namespace Drupal\config_override;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Adds an environment config override service, if DotEnv is available.
 */
class ConfigOverrideServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if (class_exists('\Symfony\Component\Dotenv\Dotenv')) {
      $drupal_root = $container->has('app.root') ? $container->get('app.root') : DRUPAL_ROOT;
      $dotenv = new Dotenv();
      $all_config_overrides = [];
      $possible_files = [$drupal_root . '/sites/default/.env', $drupal_root . '/sites/default/.environment'];

      foreach ($possible_files as $possible_file) {
        if (file_exists($possible_file)) {
          $env_content = $dotenv->parse(file_get_contents($possible_file), $possible_file);
          foreach ($env_content as $env_name => $env_value) {
            if (strpos($env_name, 'CONFIG___') === 0) {
              $env_name = strtolower($env_name);
            }
            if (strpos($env_name, 'config___') === 0) {
              list(, $config_name, $config_key) = explode('___', $env_name);
              $config_name = str_replace('__', '.', $config_name);
              $config_key = str_replace('__', '.', $config_key);
              $all_config_overrides[strtolower($config_name)][$config_key] = $env_value;
            }
          }
        }
      }

      $container->setParameter('config_override__environment', $all_config_overrides);

      $container->register('config_override.override_environment', '\Drupal\config_override\EnvironmentConfigOverride')
        ->addArgument(new Parameter('config_override__environment'))
        ->addTag('config.factory.override', ['priority' => 0]);
    }
  }

}
