<?php

namespace Drupal\messagebird\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'messagebird' widget.
 *
 * @FieldWidget(
 *   id = "telephone_messagebird_advanced",
 *   label = @Translation("MessageBird telephone number"),
 *   field_types = {
 *     "messagebird",
 *   }
 * )
 */
class MessageBirdWidget extends MessageBirdTelephoneWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['country_code'] += array(
      '#default_value' => isset($items[$delta]->country_code) ? $items[$delta]->country_code : '',
      '#required' => ($this->getSetting('country_code') === DRUPAL_REQUIRED) ?: FALSE,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $element = parent::settingsForm($form, $form_state);

    // Required is now an option, because we can store this information.
    $element['country_code']['#options'][DRUPAL_REQUIRED] = t('Required');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    switch ($this->getSetting('country_code')) {
      case DRUPAL_REQUIRED;
        $summary[] = $this->t('Country field: @status', array('@status' => $this->t('Required')));
        break;
    }

    return array_merge($summary, parent::settingsSummary());
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);

    foreach ($values as $delta => &$value) {

      /** @var \Drupal\messagebird\MessageBirdLookupInterface $lookup_service */
      $lookup_service = $form_state->get(array('lookup', $delta));

      if ($lookup_service && $lookup_service->hasValidLookup()) {
        $value['country_code'] = $lookup_service->getCountryCode();
        $value['country_prefix'] = $lookup_service->getCountryPrefix();
        $value['type'] = $lookup_service->getType();
        $value['number'] = $lookup_service->getFormatNumber();
        $value['international'] = $lookup_service->getFormatInternational();
        $value['national'] = $lookup_service->getFormatNational();
        $value['rfc3966'] = $lookup_service->getFormatRfc3966();

        // @todo Add HLR data.
      }
    }

    return $values;
  }

}
