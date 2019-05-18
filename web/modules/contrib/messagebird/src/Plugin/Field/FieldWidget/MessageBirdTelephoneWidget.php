<?php

namespace Drupal\messagebird\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManager;
use Drupal\telephone\Plugin\Field\FieldWidget\TelephoneDefaultWidget;

/**
 * Plugin implementation of the 'telephone' widget with MessageBird.
 *
 * @FieldWidget(
 *   id = "telephone_messagebird",
 *   label = @Translation("Telephone number (extended by MessageBird)"),
 *   field_types = {
 *     "telephone",
 *   }
 * )
 */
class MessageBirdTelephoneWidget extends TelephoneDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $country_status = $this->getSetting('country_code');
    if ($country_status) {
      $element['country_code'] = array(
        '#type' => 'select',
        '#title' => $this->t('Country'),
        '#description' => $this->t('Choose the country of the %title origin.', array('%title' => $element['#title'])),
        '#title_display' => 'before',
        '#empty_option' => $this->t('- Choose  -'),
        '#options' => CountryManager::getStandardList(),
      );
    }

    $element['value'] = array(
      '#type' => 'tel',
      '#title' => $element['#title'],
      '#description' => $this->t('Only numbers or plus (+) sign, no dashes or spaces.'),
      '#title_display' => 'before',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#placeholder' => $this->getSetting('placeholder'),
    );

    // If cardinality is 1, ensure a proper label is output for the field.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() == 1) {
      $element += array(
        '#type' => 'fieldset',
      );
    }

    $element['#element_validate'][] = array(
      get_class($this),
      'validateFormElement',
    );

    return $element;
  }

  /**
   * Form element validation handler for URL alias form element.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateFormElement(array &$element, FormStateInterface $form_state) {
    $value = &$element['value']['#value'];
    $country = $element['country_code']['#value'];
    $delta = $element['#delta'];

    if ($value === '') {
      return;
    }

    /** @var \Drupal\messagebird\MessageBirdLookupInterface $lookup_service */
    $lookup_service = $form_state->get(array('lookup', $delta));

    if ($lookup_service && $lookup_service->hasValidLookup() && $lookup_service->getFormatE164() == $value) {
      return;
    }

    /** @var \Drupal\messagebird\MessageBirdLookupInterface $lookup_service */
    $lookup_service = \Drupal::service('messagebird.lookup');
    $lookup_service->lookupNumber($value, $country);

    if ($lookup_service->hasValidLookup()) {
      $form_state->set(array('lookup', $delta), $lookup_service);
    }
    else {
      $form_state->setError($element, t('@name field is not a valid number.', array(
        '@name' => $element['#title'],
      )));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function messageFormValues(FieldItemListInterface $values, array $form, FormStateInterface $form_state) {
    $values = parent::extractFormValues($values, $form, $form_state);

    foreach ($values as $delta => &$value) {
      /** @var \Drupal\messagebird\MessageBirdLookupInterface $lookup_service */
      $lookup_service = $form_state->get(array('lookup', $delta));
      if ($lookup_service && $lookup_service->hasValidLookup()) {
        $value['value'] = $lookup_service->getFormatE164();
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'country_code' => DRUPAL_OPTIONAL,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element['country_code'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Show country select'),
      '#default_value' => $this->getSetting('country_code'),
      '#options' => array(
        DRUPAL_DISABLED => $this->t('Disabled'),
        DRUPAL_OPTIONAL => $this->t('Optional'),
      ),
      '#description' => $this->t('By selecting the country code of the telephone number, the validation will support national formats.'),
    );

    $element += parent::settingsForm($form, $form_state);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $country = $this->getSetting('country_code');

    switch ($country) {
      case DRUPAL_DISABLED:
        $summary[] = $this->t('Country field: @status', array('@status' => $this->t('Disabled')));
        break;

      case DRUPAL_OPTIONAL:
        $summary[] = $this->t('Country field: @status', array('@status' => $this->t('Optional')));
        break;
    }

    return array_merge($summary, parent::settingsSummary());
  }

}
