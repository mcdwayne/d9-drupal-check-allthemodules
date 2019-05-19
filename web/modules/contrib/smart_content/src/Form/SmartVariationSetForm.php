<?php

namespace Drupal\smart_content\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Class SmartVariationSetForm.
 *
 * @package Drupal\smart_content\Form
 */
class SmartVariationSetForm extends EntityForm {

  /**
   * @var \Drupal\smart_content\Entity\SmartVariationSet
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // Load the form for the variation_set_type.
    if ($variation_set_type = $this->entity->getVariationSetType()) {
      self::pluginForm($variation_set_type, $form, $form_state, ['variation_set_type']);
    }
    $form['#attached']['library'][] = 'smart_content/form';
    $form['#process'][] = [$this, 'processForm'];
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function processForm($element, FormStateInterface $form_state, $form) {
    // Attempt to get the entity from $form_state storage.
    $element = parent::processForm($element, $form_state, $form);
    if ($entity = SmartVariationSetForm::getEntityState($form_state, $element['#array_parents'])) {
      $this->entity = $entity;
    }
    // Save entity to $form_state storage.
    SmartVariationSetForm::saveEntityState($form_state, $element['#array_parents'], $this->entity);
    return $element;
  }

  /**
   * Utility function for attaching plugin forms.
   *
   * This function attaches forms for plugins implementing
   * Drupal\Core\Plugin\PluginFormInterface.  The plugin form is automatically
   * provided a Drupal\Core\Form\SubformState for tracking $form_state at the
   * plugin level.
   *
   * @param \Drupal\Component\Plugin\PluginBase $plugin
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $parents
   */
  public static function pluginForm($plugin, array &$form, FormStateInterface $form_state, $parents) {
    // If plugin implements PluginFormInterface, create SubFormState and attach.
    if ($plugin instanceof PluginFormInterface) {
      if (!$plugin_form = NestedArray::getValue($form, $parents)) {
        $plugin_form = [];
      }
      $plugin_form_state = SubformState::createForSubform($plugin_form, $form, $form_state);
      $plugin_form = $plugin->buildConfigurationForm($plugin_form, $plugin_form_state);
      $plugin_form['#tree'] = TRUE;
      // Set PluginForm within array parents.
      NestedArray::setValue($form, $parents, $plugin_form);
    }
  }

  /**
   * Utility function for validating plugin forms.
   *
   * @param \Drupal\Component\Plugin\PluginBase plugin
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $parents
   */
  public static function pluginFormValidate($plugin, array &$form, FormStateInterface $form_state, array $parents) {
    // If plugin implements PluginFormInterface, validate form.
    if ($plugin instanceof PluginFormInterface) {
      if (!$plugin_form = NestedArray::getValue($form, $parents)) {
        $plugin_form = [];
      }
      $plugin_form_state = SubformState::createForSubform($plugin_form, $form, $form_state);
      $plugin->validateConfigurationForm($plugin_form, $plugin_form_state);
    }
  }

  /**
   * Utility function for submitting plugin forms.
   *
   * @param \Drupal\Component\Plugin\PluginBase plugin
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $parents
   */
  public static function pluginFormSubmit($plugin, array &$form, FormStateInterface $form_state, array $parents) {
    // If plugin implements PluginFormInterface, submit form.
    if ($plugin instanceof PluginFormInterface) {
      if (!$plugin_form = NestedArray::getValue($form, $parents)) {
        $plugin_form = [];
      }
      $plugin_form_state = SubformState::createForSubform($plugin_form, $form, $form_state);
      $plugin->submitConfigurationForm($plugin_form, $plugin_form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if ($variation_set_type = $this->entity->getVariationSetType()) {
      self::pluginFormValidate($variation_set_type, $form, $form_state, ['variation_set_type']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    if ($variation_set_type = $this->entity->getVariationSetType()) {
      self::pluginFormSubmit($variation_set_type, $form, $form_state, ['variation_set_type']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();
    // After saving, reload entity from database.
    $this->entity = \Drupal::entityTypeManager()
      ->getStorage('smart_variation_set')->load($this->entity->id());
    // Save newly saved entity to $form_state storage.
    SmartVariationSetForm::saveEntityState($form_state, $form['#array_parents'], $this->entity);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Variation Set.', [
          '%label' => $this->entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Variation Set.', [
          '%label' => $this->entity->label(),
        ]));
    }
  }


  public static function saveEntityState($form_state, $parents, $entity) {
    NestedArray::setValue($form_state->getStorage(), array_merge(['variation_sets'], $parents, ['entities']), $entity);
  }

  public static function getEntityState($form_state, $parents) {
    return NestedArray::getValue($form_state->getStorage(), array_merge(['variation_sets'], $parents, ['entities']));
  }

}

