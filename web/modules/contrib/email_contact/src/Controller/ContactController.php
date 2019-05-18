<?php
namespace Drupal\email_contact\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\email_contact\Form\ContactForm;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Class ContactController.
 *
 * @package Drupal\email_contact\Controller
*/
class ContactController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function getTitle($entity_type = NULL, $entity_id = NULL, $field_name = NULL, $view_mode = 'full') {
    $entity = \Drupal::entityManager()->getStorage($entity_type)->load($entity_id);
    $title = $entity->getTitle() ? $entity->getTitle() . '  - Email Contact' : 'Email Contact';
    return $title;
  }

  /**
   * {@inheritdoc}
   */
  public function content($entity_type = NULL, $entity_id = NULL, $field_name = NULL, $view_mode = 'full') {
    $entity = \Drupal::entityManager()->getStorage($entity_type)->load($entity_id);
    $view_display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
    $field_display = $view_display->getComponent($field_name);
    $field_settings = $field_display['settings'];

    try {
      $form = new ContactForm($entity_type, $entity_id, $field_name, $field_settings);
      return \Drupal::formBuilder()->getForm($form);
    }
    catch (NotFoundHttpException $e) {
      \Drupal::logger('email_contact')->notice('Invalid contact form on @entity_type id @id.', ['@entity_type' => $entity->getEntityTypeId(), '@id' => $entity->id()]);
    }
  }
}
