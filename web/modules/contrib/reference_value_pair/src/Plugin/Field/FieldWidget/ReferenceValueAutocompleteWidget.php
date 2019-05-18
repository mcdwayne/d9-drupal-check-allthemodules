<?php

namespace Drupal\reference_value_pair\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'reference_value_autocomplete_widget' widget.
 *
 * @FieldWidget(
 *   id = "reference_value_autocomplete_widget",
 *   label = @Translation("Reference value autocomplete"),
 *   field_types = {
 *     "reference_value_pair"
 *   }
 * )
 */
class ReferenceValueAutocompleteWidget extends WidgetBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'size_er' => 60,
      'placeholder_er' => '',
      'match_operator' => 'CONTAINS',
      'size_value' => 60,
      'placeholder_value' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size_er'] = array(
      '#type' => 'number',
      '#title' => $this->t('Size of the entity reference textfield'),
      '#default_value' => $this->getSetting('size_er'),
      '#required' => TRUE,
      '#min' => 1,
    );
    $elements['placeholder_er'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Entity reference placeholder'),
      '#default_value' => $this->getSetting('placeholder_er'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );
    $elements['match_operator'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Autocomplete matching'),
      '#default_value' => $this->getSetting('match_operator'),
      '#options' => $this->getMatchOperatorOptions(),
      '#description' => $this->t('Select the method used to collect autocomplete suggestions. Note that <em>Contains</em> can cause performance issues on sites with thousands of entities.'),
    );
    $elements['size_value'] = array(
      '#type' => 'number',
      '#title' => $this->t('Size of the value textfield'),
      '#default_value' => $this->getSetting('size_value'),
      '#min' => 1,
      '#required' => TRUE,
    );
    $elements['placeholder_value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Value placeholder'),
      '#default_value' => $this->getSetting('placeholder_value'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Textfield Entity Reference size: @size', array('@size' => $this->getSetting('size_er')));
    if (!empty($this->getSetting('placeholder_er'))) {
      $summary[] = $this->t('Placeholder Entity Reference: @placeholder', array('@placeholder' => $this->getSetting('placeholder_er')));
    }
    else {
      $summary[] = $this->t('No Placeholder Entity Reference');
    }

    $operators = $this->getMatchOperatorOptions();
    $summary[] = $this->t('Autocomplete matching: @match_operator', array('@match_operator' => $operators[$this->getSetting('match_operator')]));
    $summary[] = $this->t('Textfield Value size: @size', array('@size' => $this->getSetting('size_value')));
    $placeholder = $this->getSetting('placeholder_value');
    if (!empty($placeholder)) {
      $summary[] = $this->t('Placeholder Value: @placeholder', array('@placeholder' => $placeholder));
    }
    else {
      $summary[] = $this->t('No Placeholder Value');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $original_element = $element;
    $elements = [];
    $entity = $items->getEntity();
    $referenced_entities = $items->referencedEntities();

    $element += array(
      '#type' => 'entity_autocomplete',
      '#target_type' => $this->getFieldSetting('target_type'),
      '#selection_handler' => $this->getFieldSetting('handler'),
      '#selection_settings' => $this->getFieldSetting('handler_settings'),
      // Entity reference field items are handling validation themselves via
      // the 'ValidReference' constraint.
      '#validate_reference' => FALSE,
      '#maxlength' => 1024,
      '#default_value' => isset($referenced_entities[$delta]) ? $referenced_entities[$delta] : NULL,
      '#size' => $this->getSetting('size_er'),
      '#placeholder' => $this->getSetting('placeholder_er'),
    );

    if ($this->getSelectionHandlerSetting('auto_create')) {
      $element['#autocreate'] = array(
        'bundle' => $this->getAutocreateBundle(),
        'uid' => ($entity instanceof EntityOwnerInterface) ? $entity->getOwnerId() : \Drupal::currentUser()->id(),
      );
    }
    $elements['target_id'] = $element;

    $elements['value'] = $original_element + array(
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#size' => $this->getSetting('size_value'),
      '#placeholder' => $this->getSetting('placeholder_value'),
      '#maxlength' => $this->getFieldSetting('max_length'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return isset($element['target_id']) ? $element['target_id'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      // The entity_autocomplete form element returns an array when an entity
      // was "autocreated", so we need to move it up a level.
      if (is_array($value['target_id'])) {
        unset($values[$key]['target_id']);
        $values[$key] += $value['target_id'];
      }
    }

    return $values;
  }

  /**
   * Returns the name of the bundle which will be used for autocreated entities.
   *
   * @return string
   *   The bundle name.
   */
  protected function getAutocreateBundle() {
    $bundle = NULL;
    if ($this->getSelectionHandlerSetting('auto_create')) {
      // If the 'target_bundles' setting is restricted to a single choice, we
      // can use that.
      if (($target_bundles = $this->getSelectionHandlerSetting('target_bundles')) && count($target_bundles) == 1) {
        $bundle = reset($target_bundles);
      }
      // Otherwise use the first bundle as a fallback.
      else {
        // @todo Expose a proper UI for choosing the bundle for autocreated
        // entities in https://www.drupal.org/node/2412569.
        $bundles = entity_get_bundles($this->getFieldSetting('target_type'));
        $bundle = key($bundles);
      }
    }

    return $bundle;
  }
  /**
   * Returns the value of a setting for the entity reference selection handler.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  protected function getSelectionHandlerSetting($setting_name) {
    $settings = $this->getFieldSetting('handler_settings');
    return isset($settings[$setting_name]) ? $settings[$setting_name] : NULL;
  }

  /**
   * Returns the options for the match operator.
   *
   * @return array
   *   List of options.
   */
  protected function getMatchOperatorOptions() {
    return [
      'STARTS_WITH' => $this->t('Starts with'),
      'CONTAINS' => $this->t('Contains'),
    ];
  }

}
