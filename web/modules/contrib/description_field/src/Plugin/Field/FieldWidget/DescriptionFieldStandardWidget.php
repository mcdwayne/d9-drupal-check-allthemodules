<?php

namespace Drupal\description_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of 'description_field_standard' widget.
 *
 * @FieldWidget(
 *   id = "description_field_standard",
 *   label = @Translation("Description field standard widget"),
 *   description = @Translation("A field widget used for displaying a Description"),
 *   field_types = {
 *     "description_field"
 *   }
 * )
 */
class DescriptionFieldStandardWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Token service.
   *
   * @var Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, Token $token_service) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->tokenService = $token_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('token')
    );

  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $long_description_setting = $items[$delta]->getFieldDefinition()->getSetting('long_description');
    if (empty($long_description_setting)) {
      return [];
    }

    $replaced_text = $this->tokenService->replace($long_description_setting['value']);

    return [
      '#type' => 'processed_text',
      '#format' => $long_description_setting['format'] ?? filter_default_format(),
      '#text' => $replaced_text,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isValueValid($field_value) {
    return TRUE;
  }

}
