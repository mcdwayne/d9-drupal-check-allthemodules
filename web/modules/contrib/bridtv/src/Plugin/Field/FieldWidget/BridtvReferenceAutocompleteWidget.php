<?php

namespace Drupal\bridtv\Plugin\Field\FieldWidget;

use Drupal\bridtv\BridInfoNegotiator;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;

/**
 * Plugin implementation of the 'bridtv_reference_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "bridtv_reference_autocomplete",
 *   label = @Translation("Autocomplete with player selection"),
 *   description = @Translation("An autocomplete field with player selection."),
 *   field_types = {
 *     "bridtv_reference"
 *   }
 * )
 */
class BridtvReferenceAutocompleteWidget extends EntityReferenceAutocompleteWidget implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\bridtv\BridInfoNegotiator
   */
  protected $negotiator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $negotiator = $container->get('bridtv.negotiator');
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $negotiator
    );
  }

  /**
   * Constructs a BridtvIdWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\bridtv\BridInfoNegotiator $negotiator
   *   The Brid.TV info negotiator.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, BridInfoNegotiator $negotiator) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->negotiator = $negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items->get($delta);
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['create_new'] = [
      '#markup' => $this->t('Video not existing yet? <a href="/media/add/bridtv" target="_blank">Click here</a> to create a new one.')
    ];
    $options = ['_use_default' => $this->t('- Use default -')] + $this->negotiator->getPlayersListOptions();
    $element['player'] = [
      '#type' => 'select',
      '#title' => $this->t('Player'),
      '#options' => $options,
      '#default_value' => $item->get('player')->getValue() ? $item->get('player')->getValue() : '_use_default',
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);
    foreach ($values as &$value) {
      if (!empty($value['player'])) {
        $players = $this->negotiator->getPlayersListOptions();
        if (!isset($players[$value['player']])) {
          $value['player'] = NULL;
        }
      }
    }
    return $values;
  }

}
