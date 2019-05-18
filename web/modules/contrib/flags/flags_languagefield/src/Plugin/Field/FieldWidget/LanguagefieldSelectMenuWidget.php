<?php

namespace Drupal\flags_languagefield\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\languagefield\Plugin\Field\FieldWidget\LanguageSelectWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'languagefield_select_menu' widget.
 *
 * @FieldWidget(
 *   id = "languagefield_select_menu",
 *   label = @Translation("Language select list with flags"),
 *   field_types = {}
 * )
 */
class LanguagefieldSelectMenuWidget extends LanguageSelectWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['value']['#type'] = 'select_icons';

    $mapper = \Drupal::service('flags.mapping.language');
    $element['value']['#options_attributes'] = $mapper->getOptionAttributes(
      array_keys($element['value']['#options'])
    );
    $element['value']['#attached'] = array('library' => array('flags/flags'));

    return $element;
  }

}
