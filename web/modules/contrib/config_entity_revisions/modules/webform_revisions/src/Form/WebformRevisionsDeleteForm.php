<?php

namespace Drupal\webform_revisions\Form;

use Drupal\config_entity_revisions\ConfigEntityRevisionsDeleteFormBase;
use Drupal\webform_revisions\Controller\WebformRevisionsController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a WebformRevisions revision.
 *
 * @internal
 */
class WebformRevisionsDeleteForm extends ConfigEntityRevisionsDeleteFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity_type.manager');
    return new static(
      $entity_manager->getStorage('config_entity_revisions'),
      $container->get('database'),
      $container->get('date.formatter'),
      WebformRevisionsController::create($container)
    );
  }

}
