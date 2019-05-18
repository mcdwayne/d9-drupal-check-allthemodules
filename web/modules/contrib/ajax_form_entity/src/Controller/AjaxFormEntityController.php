<?php

namespace Drupal\ajax_form_entity\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AjaxFormEntityController.
 *
 * @package Drupal\ajax_form_entity\Controller
 */
class AjaxFormEntityController extends ControllerBase {

  /**
   * Entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilder
   */
  protected $entityFormBuilder;

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityFormBuilder $entity_form_builder, EntityTypeManager $entity_type_manager) {
    $this->entityFormBuilder = $entity_form_builder;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.form_builder'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Sends back a form entity to edit any content entity.
   */
  public function ajaxForm($entity_type, $id, $popin, $view_mode) {
    // Get the entity and generate the form.
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($id);
    $form = $this->entityFormBuilder->getForm($entity);

    // If popin, return directly, else return an AJAX callback.
    if ($popin) {
      return $form;
    }
    else {
      $response = new AjaxResponse();
      $selector = '.ajax-form-entity-view-' . $entity_type . '-' . $id;
      $response->addCommand(new ReplaceCommand($selector, $form));
      return $response;
    }
  }

}
