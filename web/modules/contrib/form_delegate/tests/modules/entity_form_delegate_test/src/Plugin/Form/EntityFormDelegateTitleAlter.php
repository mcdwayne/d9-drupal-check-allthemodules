<?php

namespace Drupal\entity_form_delegate_test\Plugin\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\form_delegate\Annotation\EntityFormDelegate;
use Drupal\form_delegate\EntityFormDelegatePluginBase;
use Drupal\node\Entity\Node;

/**
 * Class EntityFormDelegateAlter
 *
 * @EntityFormDelegate(
 *   id = "test_entity_form_title_alter",
 *   entity = "node",
 *   bundle = "test_bundle",
 *   display = "test_form_display_mode",
 *   operation = {"default", "edit"},
 *   priority = 1
 * )
 *
 * @package Drupal\entity_form_delegate_test\Plugin\Form
 */
class EntityFormDelegateTitleAlter extends EntityFormDelegatePluginBase {

  /**
   * {@inheritdoc}
   */
  function buildForm(array &$form, FormStateInterface $formState) {
    $form['title']['#required'] = FALSE;
    $form['title']['#access'] = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('title')) {
      $form_state->setErrorByName('title', 'Should not have value.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    /** @var Node $entity */
    $entity = $this->getEntity();
    $entity->setTitle('Article');
    drupal_set_message('Yeah you saved it!');
  }

}
