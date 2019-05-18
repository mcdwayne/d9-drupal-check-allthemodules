<?php

namespace Drupal\mcapi\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the wallet selector widget.
 *
 * @FieldWidget(
 *   id = "wallet_reference_autocomplete",
 *   label = @Translation("Wallets"),
 *   description = @Translation("Select from all wallets"),
 *   field_types = {
 *     "wallet_reference"
 *   }
 * )
 * @todo inject \Drupal::service('plugin.manager.entity_reference_selection')
 */
class WalletReferenceAutocompleteWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + [
      'max_select' => 15,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['max_select'] = [
      '#title' => $this->t('Max number of available wallets before select element upgrades autocomplete element.'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('max_select'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    // Size.
    //unset($summary[1]);
    $message = $this->t('Max @num items in select widget', ['@num' => $this->getSetting('max_select') ]);
    $summary['hide_one'] = ['#markup' => $message];
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $referenced_entities = $items->referencedEntities();
    $default_value_wallet = isset($referenced_entities[$delta]) ? $referenced_entities[$delta] : NULL;
    if ($default_value_wallet && $default_value_wallet->system->value) {
      $wid_options = [$default_value_wallet->id() => $default_value_wallet->label()];
    }
    else {
      // Get all possible wallets the current user is permitted to pay in/out of
      $entity_ids = \Drupal::service('plugin.manager.entity_reference_selection')
        ->getSelectionHandler($this->fieldDefinition)
        ->getReferenceableEntities(NULL, 'contains');
      $wid_options = $entity_ids['mcapi_wallet'];
    }
    $count = count($wid_options);

    // Present different widgets according to the number of wallets to choose
    // from, and settings.
    if (!$count) {
      $form_state->setError($element, $this->t('No wallets to show for @role', ['@role' => $this->fieldDefinition->getLabel()]));
      $form['#disabled'] = TRUE;
      return [];
    }
    elseif ($count < $this->getSetting('max_select')) {
      $element += [
        '#type' => 'select',
        '#options' => $wid_options,
        '#default_value' => $default_value_wallet ? $default_value_wallet->id() : '',
      ];
    }
    else {
      $element += [
        '#type' => 'wallet_entity_auto', //this is just a wrapper around element entity_autocomplete
        '#target_type' => 'mcapi_wallet',
        '#selection_settings' => $this->fieldDefinition->getSetting('handler_settings'),
        '#default_value' => $default_value_wallet,
        '#placeholder' => $this->getSetting('placeholder'),
        // Not needed apparently
        //'#target_type' => 'mcapi_wallet',
        //'#selection_handler' => $this->getFieldSetting('handler'),
      ];
    }
    return ['target_id' => $element];
  }


  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return isset($element['target_id']) ? $element['target_id'] : FALSE;
  }


  /**
   * Mass payment form sets cardinality of this field on the fly.
   */
  public function cardinalitySetMultiple() {
    $this->fieldDefinition->getFieldStorageDefinition()->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
  }

}
