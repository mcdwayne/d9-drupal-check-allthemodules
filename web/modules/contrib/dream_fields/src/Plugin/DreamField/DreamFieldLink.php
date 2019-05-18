<?php

namespace Drupal\dream_fields\Plugin\DreamField;

use Drupal\dream_fields\DreamFieldPluginBase;
use Drupal\dream_fields\FieldBuilderInterface;

/**
 * Plugin implementation of 'link'.
 *
 * @DreamField(
 *   id = "link",
 *   label = @Translation("Link"),
 *   description = @Translation("This will add an input field for an internal or external URL and will be outputted with the label in the front."),
 *   weight = -6,
 *   preview = "images/url-dreamfields.png",
 *   preview_provider = "dream_fields",
 *   provider = "link",
 *   field_types = {
 *     "link"
 *   },
 * )
 */
class DreamFieldLink extends DreamFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getForm() {
    $form['collect_title'] = [
      '#title' => $this->t('Collect a title for this link'),
      '#type' => 'checkbox',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function saveForm($values, FieldBuilderInterface $field_builder) {
    $field_builder
      ->setField('link', [], [
        'title' => $values['collect_title'] ? 1 : 0,
      ])
      ->setWidget('link_default')
      ->setDisplay('link', [
        'trim_length' => NULL,
        'url_only' => FALSE,
        'url_plain' => FALSE,
        'rel' => '0',
        'target' => '0',
      ]);
  }

}
