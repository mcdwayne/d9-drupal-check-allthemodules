<?php

namespace Drupal\text_or_link\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\LinkItemInterface;
use Drupal\link\Plugin\Field\FieldType\LinkItem;
use Drupal\text_or_link\TextOrLinkItemInterface;

/**
 * Plugin implementation of the 'text_or_link' field type.
 *
 * @FieldType(
 *   id = "text_or_link",
 *   label = @Translation("Text or Link"),
 *   description = @Translation("Stores link text, an optional URL string, and optional blob of attributes to assemble a link."),
 *   default_widget = "text_or_link",
 *   default_formatter = "text_or_link",
 *   constraints = {
 *     "LinkType" = {},
 *     "LinkAccess" = {},
 *     "LinkExternalProtocols" = {},
 *     "LinkNotExistingInternal" = {}
 *   }
 * )
 */
class TextOrLinkItem extends LinkItem implements TextOrLinkItemInterface {

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);

    unset($element['title']);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'uri' => [
          'description' => 'The URI of the link.',
          'type' => 'varchar',
          'length' => 2048,
        ],
        'title' => [
          'description' => 'The link text.',
          'type' => 'varchar',
          'length' => 255,
        ],
        'options' => [
          'description' => 'Serialized array of options for the link.',
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
      ],
      'indexes' => [
        'title' => [['title', 30]],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['title'] = $random->word(mt_rand(1, 255));
    if ($field_definition->getItemDefinition()->getSetting('link_type') & LinkItemInterface::LINK_EXTERNAL) {
      // Set of possible top-level domains.
      $tlds = ['com', 'net', 'gov', 'org', 'edu', 'biz', 'info'];
      // Set random length for the domain name.
      $domain_length = mt_rand(7, 15);
      $values['uri'] = 'http://www.' . $random->word($domain_length) . '.' . $tlds[mt_rand(0, (count($tlds) - 1))];
    }
    else {
      $values['uri'] = 'base:' . $random->name(mt_rand(1, 64));
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('title')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function isUriEmpty() {
    $value = $this->get('uri')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'title';
  }

}
