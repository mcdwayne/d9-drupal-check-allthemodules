<?php

namespace Drupal\contacts_events\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormSimple;

/**
 * Inline widget for tickets.
 *
 * @FieldWidget(
 *   id = "inline_entity_form_tickets",
 *   label = @Translation("Booking Tickets"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = false
 * )
 */
class TicketInlineEntityWidget extends InlineEntityFormSimple {

  /**
   * {@inheritdoc}
   */
  protected function getTargetBundles() {
    // Don't allow creation of any other order item type other than ticket.
    return ['standard'];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['form_mode' => 'booking'] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function getFieldSetting($name) {
    $setting = parent::getFieldSetting($name);

    // Spoof the bundle settings.
    if ($name == 'handler_settings') {
      $setting['target_bundles'] = $this->getTargetBundles();
    }

    return $setting;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['override_labels']['#access'] = FALSE;
    $element['label_singular']['#access'] = FALSE;
    $element['label_plural']['#access'] = FALSE;
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($entity_form_mode = $this->getEntityFormMode()) {
      $form_mode_label = $entity_form_mode->label();
    }
    else {
      $form_mode_label = $this->t('Default');
    }
    $summary[] = $this->t('Form mode: @mode', ['@mode' => $form_mode_label]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['#field_title'] = $this->t('Edit ticket');
    $element['#title_display'] = 'invisible';
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeLabels() {
    // The admin has specified the exact labels that should be used.
    if ($this->getSetting('override_labels')) {
      return [
        'singular' => $this->getSetting('label_singular'),
        'plural' => $this->getSetting('label_plural'),
      ];
    }
    else {
      return [
        'singular' => $this->t('ticket'),
        'plural' => $this->t('tickets'),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Only allow for purchased entities on the tickets bundle of order item.
    return $field_definition->getTargetEntityTypeId() == 'commerce_order_item'
      && $field_definition->getTargetBundle() == 'contacts_ticket'
      && $field_definition->getName() == 'purchased_entity';
  }

  /**
   * {@inheritdoc}
   */
  protected function getInlineEntityForm($operation, $bundle, $langcode, $delta, array $parents, EntityInterface $entity = NULL) {
    $element = parent::getInlineEntityForm($operation, $bundle, $langcode, $delta, $parents, $entity);
    // We do want to save immediately, rather than on page submit.
    $element['#save_entity'] = TRUE;
    return $element;
  }

}
