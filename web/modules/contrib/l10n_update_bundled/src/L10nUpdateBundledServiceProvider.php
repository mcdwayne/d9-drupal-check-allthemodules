<?php

namespace Drupal\l10n_update_bundled;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Service provider used to alter the existing services.
 */
class L10nUpdateBundledServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Add a keyvalue factory for the translation statuses.
    $options = $container->getParameter('factory.keyvalue');
    $options['locale.translation_status'] = 'l10n_update_bundled.keyvalue.translation_status';
    $container->setParameter('factory.keyvalue', $options);
  }

}
