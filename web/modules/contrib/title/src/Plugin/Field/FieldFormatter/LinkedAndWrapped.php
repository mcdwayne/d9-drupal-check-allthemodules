<?php

namespace Drupal\title\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Template\Attribute;

/**
 * A field formatter for linking and wrapping text.
 *
 * @FieldFormatter(
 *   id = "linked_and_wrapped",
 *   label = @Translation("Linked & Wrapped"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class LinkedAndWrapped extends FormatterBase {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    $output = [];

    $attributes = new Attribute();
    if ($this->getSetting('tag') == 'h1') {
      $attributes->addClass('title');
      $attributes->addClass('replaced-title');
      $attributes->setAttribute('id', 'page-title');
    }

    $classes = $this->getSetting('classes');
    if (!empty($classes)) {
      $attributes->addClass($classes);
    }

    $parent = $items->getParent()->getValue();
    foreach ($items as $item) {
      $text = $item->getValue()['value'];
      if ($this->getSetting('linked')) {
        $text = $this->l($text, $parent->toUrl());
      }
      $output[] = [
        '#type' => 'html_tag',
        '#tag' => $this->getSetting('tag'),
        '#attributes' => $attributes->toArray(),
        '#value' => $text,
      ];
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $heading_options = [
      'span' => 'span',
      'div' => 'div',
    ];
    foreach (range(1, 5) as $level) {
      $heading_options['h' . $level] = 'H' . $level;
    }
    $form['tag'] = [
      '#title' => $this->t('Tag'),
      '#type' => 'select',
      '#description' => $this->t('Select the tag which will be wrapped around the text.'),
      '#options' => $heading_options,
      '#default_value' => $this->getSetting('tag'),
    ];
    $form['linked'] = [
      '#title' => $this->t('Link to the Content'),
      '#type' => 'checkbox',
      '#description' => $this->t('Wrap the text with a link to the content.'),
      '#default_value' => $this->getSetting('linked'),
    ];
    $form['classes'] = [
      '#title' => $this->t('Classes'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('classes'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'tag' => 'h2',
      'linked' => '1',
      'classes' => '',
    ];
  }
}
