<?php

/**
 * @file
 * Configures the container.
 */

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Implements hook_inject_init().
 */
function wow_inject_init(ContainerBuilder $container) {
  $services_config = array();
  $languages_region = array();
  // Loops through the configuration of services.
  foreach (wow_service_list() as $language => $service) {
    // Check if service is enabled.
    if ($service->enabled) {
      // If it does, build an array of locales supported by this region.
      $services_config[$service->region][$service->language] = $service->locale;
    }
  }

  $key = variable_get('wow_key');
  $public_key = variable_get('wow_public_key');
  foreach ($services_config as $region => $locales) {
    // Configures the service to return based on private key declaration.
    // Check openssl extension is loaded in order to make HTTPS calls.
    if (empty($key) || empty($public_key) || !extension_loaded('openssl')) {
      // If it does not, configure an HTTP service.
      $container->register("wow.service.$region", 'WoW\Core\Service\ServiceHttp')
        ->addArgument($region)
        ->addArgument($locales);
    }
    else {
      // If it does, configure an HTTPS service.
      $container->register("wow.service.$region", 'WoW\Core\Service\ServiceHttps')
        ->addArgument($region)
        ->addArgument($locales)
        ->addArgument($public_key)
        ->addArgument($key);
    }

    // DataService are created through a DataService factory.
    $container->setDefinition("wow.data.$region", new Definition(
        'WoW\Core\Data\DataService',
        array(new Reference("wow.service.$region"))
      ))
      ->setFactoryClass('WoW\Core\Data\DataServiceFactory')
      ->setFactoryMethod('get');

    // Registers the languages-to-region look-up array in the container.
    foreach (array_keys($locales) as $language) {
      $container->setAlias("wow.service.lang.$language", "wow.service.$region");
      $container->setAlias("wow.data.lang.$language", "wow.data.$region");
    }
  }

  // Configures service and data controllers.
  foreach (entity_get_info() as $entity_type => $info) {
    // Check the entity belong to the 'wow' package.
    if (!strncmp('wow', $entity_type, 3)) {
      // If it does, creates a storage controller definition.
      $container->setDefinition("entity.controller.$entity_type", new Definition(
          'EntityAPIControllerInterface',
          array($entity_type)
        ))
        ->setFactoryClass('WoW\Core\Entity\EntityStorageControllerFactory')
        ->setFactoryMethod('get');
      // Creates the storage controller reference.
      $storage = new Reference("entity.controller.$entity_type");

      if (isset($info['service controller class'])) {
        // If the controller needs a service class, inject it.
        $container->setDefinition("wow.controller.$entity_type", new Definition(
            $info['service controller class'],
            array($entity_type, $storage)
        ));
      }
      else if (isset($info['data controller class'])) {
        // If the controller needs a data service class, inject it.
        $container->setDefinition("wow.controller.$entity_type", new Definition(
            $info['data controller class'],
            array($entity_type, $storage)
        ));
      }
    }
  }

}
