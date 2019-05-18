<?php

namespace Drupal\getresponse_forms;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Utility\Html;

/**
 * Provides a GetResponse form builder.
 */
class GetresponseFormsFormViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $signup = $entity;

    $form = new \Drupal\getresponse_forms\Form\GetresponseFormsPageForm();

    $form_id = 'getresponse_forms_subscribe_block_' . $signup->id . '_form';
    $form->setFormID($form_id);
    $form->setSignup($signup);
    $content = \Drupal::formBuilder()->getForm($form);
    $content['#cache']['contexts'][] = 'user.permissions';

    return $content;
  }


  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    $build = [];
    foreach ($entities as $key => $entity) {
      $build[$key] = $this->view($entity, $view_mode, $langcode);
    }
    return $build;
  }

}
