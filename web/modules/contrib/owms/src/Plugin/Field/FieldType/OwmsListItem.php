<?php

namespace Drupal\owms\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\options\Plugin\Field\FieldType\ListStringItem;
use Drupal\owms\Entity\OwmsData;

/**
 * Defines the 'owms_list_item' field type.
 *
 * @FieldType(
 *   id = "owms_list_item",
 *   label = @Translation("OWMS List item"),
 *   category = @Translation("Text"),
 *   default_widget = "options_select",
 *   default_formatter = "list_default",
 * )
 */
class OwmsListItem extends ListStringItem {


  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    $owms_config = $this->getSetting('owms_config');
    if ($owms_config) {
      $owmsData = OwmsData::load($owms_config);
      return $owmsData->getValidItems();
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'owms_config' => '',
    ];
  }


  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $owms_objects = OwmsData::loadMultiple();
    foreach ($owms_objects as $owmsData) {
      $options[$owmsData->id()] = $owmsData->label();
    }
    $element['owms_config'] = [
      '#type' => 'select',
      '#title' => $this->t('OWMS Configuration'),
      '#default_value' => $this->getSetting('owms_config'),
      '#disabled' => $has_data,
      '#options' => $options,
      '#required' => TRUE,
    ];

    return $element;
  }

}
