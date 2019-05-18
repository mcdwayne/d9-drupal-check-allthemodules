<?php

namespace Drupal\errw\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'entity_reference_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "errw",
 *   label = @Translation("Autocomplete - Rendered"),
 *   description = @Translation("An autocomplete text field, also renders
 *   entity."),
 *   field_types = {   "entity_reference"   }
 * )
 */
class ErrwWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['errw_fields'] = [
      '#type' => 'textfield',
      '#title' => t('Rendered Fields'),
      '#default_value' => $this->getSetting('errw_fields'),
      '#description' => t('Comma separated list of field names (machine name only).'),
    ];
    $element['errw_template'] = [
      '#type' => 'textfield',
      '#title' => t('Rendered Template'),
      '#default_value' => $this->getSetting('errw_template'),
      '#description' => t('Template used to render fields. {} will be replaced by value of select fields. So [{}] will become [some_field_value, other_field_value]. Default is [{}]'),
    ];
    $element['errw_glue'] = [
      '#type' => 'textfield',
      '#title' => t('Rendered Concatenation String'),
      '#default_value' => $this->getSetting('errw_glue'),
      '#description' => t('String used to concatenate fields. default is a comma with an space.'),
    ];
    $element['errw_field_label_template'] = [
      '#type' => 'textfield',
      '#title' => t('Field label template'),
      '#default_value' => $this->getSetting('errw_field_label_template'),
      '#description' => t("[] will be replaced by field's title, and {} with fields value."),
    ];
    $element['errw_prepend_entity_label'] = [
      '#type' => 'checkbox',
      '#title' => t('Prepend Entity Label'),
      '#default_value' => $this->getSetting('errw_prepend_entity_label'),
      '#description' => t('Whether if label of entity should be prepended to final value. Default is Yes.'),
    ];
    $element['errw_titled_fields'] = [
      '#type' => 'textfield',
      '#title' => t('Titled Fields'),
      '#default_value' => $this->getSetting('errw_titled_fields'),
      '#description' => t("Fields whose title is also rendered according to the given template. If a field is not listed simply it's value is shown."),
    ];
    $element['errw_all_titled'] = [
      '#type' => 'checkbox',
      '#title' => t('Title all the fields'),
      '#default_value' => $this->getSetting('errw_all_titled'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $ret = parent::defaultSettings();

    $ret['errw_fields'] = '';
    $ret['errw_template'] = '[{}]';
    $ret['errw_field_label_template'] = '[]: {}';
    $ret['errw_glue'] = ', ';
    $ret['errw_all_titled'] = TRUE;
    $ret['errw_titled_fields'] = '';
    $ret['errw_prepend_entity_label'] = '';

    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $ret = parent::formElement($items, $delta, $element, $form, $form_state);
    $ret['target_id']['#type'] = 'errw';
    foreach ([
               'fields',
               'template',
               'field_label_template',
               'glue',
               'all_label',
               'titled_fields',
             ] as $thing) {
      $ret['target_id']['#selection_settings']["errw_$thing"] = $this->getSetting("errw_$thing");
    }
    return $ret;
  }

}
