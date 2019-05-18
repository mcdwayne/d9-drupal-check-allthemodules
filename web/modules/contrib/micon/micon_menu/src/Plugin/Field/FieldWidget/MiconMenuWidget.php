<?php

namespace Drupal\micon_menu\Plugin\Field\FieldWidget;

use Drupal\micon_link\Plugin\Field\FieldWidget\MiconLinkWidget;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'link' widget.
 *
 * @FieldWidget(
 *   id = "micon_menu",
 *   label = @Translation("Menu Link (with icon)"),
 *   no_ui = FALSE,
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class MiconMenuWidget extends MiconLinkWidget {

  /**
   * {@inheritdoc}
   */
  protected function getPackages() {
    $config = \Drupal::config('micon_menu.config');
    return $config->get('packages');
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['options']['attributes']['data-icon']['#access'] = \Drupal::currentUser()->hasPermission('use micon menu');
    return $element;
  }

  /**
   * Recursively clean up options array if no data-icon is set.
   */
  public static function validateIconElement($element, FormStateInterface $form_state, $form) {
    parent::validateIconElement($element, $form_state, $form);
    if ($values = $form_state->getValue('link')) {
      foreach ($values as $value) {
        // Support menu_link_attributes module.
        if ($attributes = $form_state->getValue('attributes')) {
          if (!empty($value['options']['attributes'])) {
            $attributes += $value['options']['attributes'];
            $form_state->setValue('attributes', $attributes);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Only make this widget available to menu_link_content.
    return $field_definition->getTargetEntityTypeId() == 'menu_link_content';
  }

}
