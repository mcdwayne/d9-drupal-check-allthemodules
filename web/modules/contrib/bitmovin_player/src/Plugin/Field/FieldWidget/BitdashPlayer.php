<?php

namespace Drupal\bitdash_player\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;

/**
 * Field widget class for the Bitdash player.
 *
 * @FieldWidget(
 *  id = "bitdash_player",
 *  label = @Translation("Bitdash Player"),
 *  field_types = {"bitdash_player"}
 * )
 */
class BitdashPlayer extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $field_settings = $this->getFieldSettings();
    return $element;
  }

}
