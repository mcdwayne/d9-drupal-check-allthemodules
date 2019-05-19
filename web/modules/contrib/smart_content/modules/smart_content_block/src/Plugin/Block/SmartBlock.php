<?php

namespace Drupal\smart_content_block\Plugin\Block;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\smart_content\Entity\SmartVariationSet;
use Drupal\smart_content\Form\SmartVariationSetForm;

/**
 * Provides a 'SmartBlock' block.
 *
 * @Block(
 *  id = "smart_block",
 *  admin_label = @Translation("Smart block"),
 * )
 */
class SmartBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'variation_set' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $configuration = $this->getConfiguration();
    // @todo: make this entire section more pluggable.
    /** @var SmartVariationSet $entity */
    $entity = \Drupal::entityTypeManager()
      ->getStorage('smart_variation_set')->load($configuration['variation_set']);

    return [
      'smart_content' => $entity->renderPlaceholder()
    ];
  }

  public function blockForm($form, FormStateInterface $form_state) {
    $form['#process'][] = [$this, 'buildWidget'];
    return $form;
  }


  /**
   * Render API callback: builds the formatter settings elements.
   */
  public function buildWidget(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $configuration = $this->getConfiguration();
    // Get array parents to track state when form is embedded.
    $parents = $element['#array_parents'];
    // Append child to track state based on nested SmartVariationSetForm.
    $parents[] = 'variation_set_config';
    // First attempt to load state from form.
    if (!$entity = SmartVariationSetForm::getEntityState($form_state, $parents)) {
      // If entity exists, load entity.
      if (!empty($configuration['variation_set'])) {
        $entity = \Drupal::entityTypeManager()
          ->getStorage('smart_variation_set')->load($configuration['variation_set']);
      }
      // Else, create a new entity.
      else {
        $entity = \Drupal::entityTypeManager()
          ->getStorage('smart_variation_set')
          ->create([]);
      }
      // Attach the VariationSetType decorator for block handling.
      $smart_variation_set_type = \Drupal::service('plugin.manager.smart_content.variation_set_type')->createInstance('block', [], $entity);
      $entity->setVariationSetType($smart_variation_set_type);
    }
    // Store the loaded entity to state based on #array_parents and child key.
    SmartVariationSetForm::saveEntityState($form_state, $parents, $entity);
    // Load the existing configuration form if already exists(rebuild), otherwise return empty array.
    $variation_set_form = !empty($element['variation_set_config']) ? $element['variation_set_config'] : [];
    // Create subform state.
    $variation_set_form_state = SubformState::createForSubform($variation_set_form, $element, $form_state);
    // Retrieve the entity edit form object, and set the entity and build the form.
    $variation_set_form = \Drupal::entityTypeManager()->getFormObject('smart_variation_set', 'edit')->setEntity($entity)->buildForm($variation_set_form, $variation_set_form_state);

    // Remove action buttons from SmartVariationSetForm, block submit will trigger instead..
    unset($variation_set_form['actions']);
    // Embed form.
    $element['variation_set_config'] = $variation_set_form;
    return $element;
  }

  public function blockValidate($form, FormStateInterface $form_state) {
    parent::blockValidate($form, $form_state);
    if ($form_state instanceof SubformStateInterface) {
      $parent_form = $form_state->getCompleteForm();
      $parent_form_state = $form_state->getCompleteFormState();
    }
    else {
      $parent_form = $form;
      $parent_form_state = $form_state;
    }
    $parents = isset($form['#form_id']) && $form['#form_id'] == 'block_form' ? ['settings'] : $form['#array_parents'];
    $parents[] = 'variation_set_config';

    if (!$variation_set_form = NestedArray::getValue($parent_form, $parents)) {
      $variation_set_form = [];
    }
    $variation_set_form_state = SubformState::createForSubform($variation_set_form, $parent_form, $parent_form_state);
    $entity = SmartVariationSetForm::getEntityState($form_state, $parents);
    if ($entity) {
      $form_object = \Drupal::entityTypeManager()->getFormObject('smart_variation_set', 'edit')->setEntity($entity);
      $form_object->validateForm($variation_set_form, $variation_set_form_state);
    }
  }

  /**
  * {@inheritdoc}
  */
  public function blockSubmit($form, FormStateInterface $form_state) {
    if ($form_state instanceof SubformStateInterface) {
      $parent_form = $form_state->getCompleteForm();
      $parent_form_state = $form_state->getCompleteFormState();
    }
    else {
      $parent_form = $form;
      $parent_form_state = $form_state;
    }
    $parents = isset($form['#form_id']) && $form['#form_id'] == 'block_form' ? ['settings'] : $form['#array_parents'];
    $parents[] = 'variation_set_config';

   if (!$variation_set_form = NestedArray::getValue($parent_form, $parents)) {
      $variation_set_form = [];
    }
    $variation_set_form_state = SubformState::createForSubform($variation_set_form, $parent_form, $parent_form_state);
    $entity = SmartVariationSetForm::getEntityState($form_state, $parents);
    if ($entity) {
      $form_object = \Drupal::entityTypeManager()->getFormObject('smart_variation_set', 'edit')->setEntity($entity);
      $form_object->submitForm($variation_set_form, $variation_set_form_state);
      // Save the entity.  Avoid calling save on form so storage is not lost.
      $entity->save();
      SmartVariationSetForm::saveEntityState($form_state, $parents, $entity);
      $this->setConfigurationValue('variation_set', $entity->id());
    }

  }

}
