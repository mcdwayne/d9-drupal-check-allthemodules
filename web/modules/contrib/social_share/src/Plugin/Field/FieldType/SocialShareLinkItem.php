<?php

namespace Drupal\social_share\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\social_share\SocialShareLinkManagerTrait;

/**
 * Plugin implementation of the 'social_share_link' field type.
 *
 * @todo: Make allowed options and their order configurable.
 *
 * @FieldType(
 *   id = "social_share_link",
 *   label = @Translation("Social share link"),
 *   description = @Translation("Allows selecting social share links."),
 *   category = @Translation("Other"),
 *   default_widget = "options_buttons",
 *   default_formatter = "list_default",
 * )
 */
class SocialShareLinkItem extends FieldItemBase implements OptionsProviderInterface {

  use SocialShareLinkManagerTrait;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Text value'))
      ->addConstraint('Length', ['max' => 255])
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    return array_keys($this->getPossibleOptions($account));
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    return $this->getSettableOptions($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    return array_keys($this->getSettableOptions($account));
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    $all_plugins = array_map(function ($definition) {
      return $definition['label'];
    }, $this->getSocialShareLinkManager()->getDefinitions());

    $allowed_plugins = $this->getSetting('allowed_values') ? explode("\r\n", $this->getSetting('allowed_values')) : array_keys($this->getSocialShareLinkManager()->getDefinitions());
    $options = [];
    foreach ($allowed_plugins as $plugin_id) {
      $options[$plugin_id] = $all_plugins[$plugin_id];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = parent::storageSettingsForm($form, $form_state, $has_data);

    $element['allowed_values'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed plugins'),
      '#description' => $this->t('Allows restricting and ordering the allowed plugins. List one plugin ID per line.'),
      '#default_value' => $this->getSetting('allowed_values') ?: implode("\r\n", array_keys($this->getSocialShareLinkManager()->getDefinitions())),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return parent::defaultStorageSettings() + [
      // If NULL is given, all plugins are used in default order.
      'allowed_values' => NULL,
    ];
  }

}
