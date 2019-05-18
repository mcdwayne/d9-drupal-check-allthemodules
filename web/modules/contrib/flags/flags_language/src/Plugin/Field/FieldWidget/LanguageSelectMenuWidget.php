<?php

namespace Drupal\flags_language\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\LanguageSelectWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'language_select_menu' widget.
 *
 * @FieldWidget(
 *   id = "language_select_menu",
 *   label = @Translation("Language select with flags"),
 *   field_types = {}
 * )
 */
class LanguageSelectMenuWidget extends LanguageSelectWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    // Add #options to the $element.
    $element['value'] = language_process_language_select($element['value']);
    // Change language select to the out type.
    $element['value']['#type'] = 'select_icons';

    $mapper = \Drupal::service('flags.mapping.language');
    $element['value']['#options_attributes'] = $mapper->getOptionAttributes(
      array_keys($element['value']['#options'])
    );

    $element['value']['#attached'] = array('library' => array('flags/flags'));

    // @TODO: check this language_element_info_alter.
    return $element;
  }

}
