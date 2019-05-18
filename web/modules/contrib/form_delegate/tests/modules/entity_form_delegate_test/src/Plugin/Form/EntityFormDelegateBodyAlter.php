<?php

namespace Drupal\entity_form_delegate_test\Plugin\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\form_delegate\EntityFormDelegatePluginBase;
use Drupal\form_delegate\Annotation\EntityFormDelegate;
use Drupal\node\Entity\Node;

/**
 * Class EntityFormDelegateAlter
 *
 * @EntityFormDelegate(
 *   id = "test_entity_form_body_alter",
 *   entity = "node",
 *   bundle = "test_bundle",
 *   display = "test_form_display_mode",
 *   operation = {"default", "edit"},
 *   priority = 2
 * )
 *
 * @package Drupal\entity_form_delegate_test\Plugin\Form
 */
class EntityFormDelegateBodyAlter extends EntityFormDelegatePluginBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    /** @var Node $entity */
    $entity = $this->getEntity();
    $entity->set('body', 'This text overwrites the original value of the body.');
    drupal_set_message('Yeah you saved it!');
  }

}
