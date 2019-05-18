<?php

namespace Drupal\entity_pilot\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\entity_pilot\CustomsInterface;
use Drupal\entity_pilot\LegacyMessagingTrait;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'ep_approved_passengers' widget.
 *
 * @FieldWidget(
 *   id = "ep_approved_passengers",
 *   label = @Translation("Approved passengers grid"),
 *   field_types = {
 *     "ep_approved_passengers",
 *   },
 *   multiple_values = TRUE
 * )
 */
class ApprovedPassengersSelect extends OptionsButtonsWidget implements ContainerFactoryPluginInterface {

  use LegacyMessagingTrait;

  /**
   * Current logged in user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Customs handler.
   *
   * @var \Drupal\entity_pilot\CustomsInterface
   */
  protected $customs;

  /**
   * The logger service for the entity_pilot channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Available passengers.
   *
   * @var array
   */
  protected $options = [];

  /**
   * Constructs a new ApprovedPassengersSelect object.
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
   *   Any third party settings settings.
   * @param \Drupal\entity_pilot\CustomsInterface $customs
   *   The customs (incoming/dependency/importing) handler.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger service for entity_pilot channel.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, CustomsInterface $customs, AccountProxyInterface $current_user, LoggerInterface $logger) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->currentUser = $current_user;
    $this->customs = $customs;
    $this->logger = $logger;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_pilot.customs'),
      $container->get('current_user'),
      $container->get('logger.factory')->get('entity_pilot')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $options = $this->getOptions($items->getEntity());
    $selected = $this->getSelectedOptions($items);

    $header = [
      'id' => $this->t('ID'),
      'label' => $this->t('Label'),
      'entity_type' => $this->t('Type'),
      'preview' => $this->t('Preview'),
      'existing' => $this->t('Existing'),
      'diff' => $this->t('Diff'),
    ];
    $element['#type'] = 'tableselect';
    $element += [
      '#multiple' => $this->multiple,
      '#default_value' => $this->multiple ? $selected : ($selected ? reset($selected) : NULL),
      '#empty' => $this->t('This flight contains no entities'),
      '#header' => $header,
      '#options' => $options,
      '#access' => !empty($options),
    ];
    $element['#attached']['library'][] = 'core/drupal.ajax';
    $element['#attached']['library'][] = 'core/drupal.dialog';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    if (empty($this->options)) {
      $options = [];
      // Limit the settable options for the current user account.
      try {
        foreach ($this->customs->screen($entity) as $passenger_id => $unsaved_entity) {
          $existing = $this->t('No');
          $diff = $this->t('N/A');
          if ($exists = $this->customs->exists($unsaved_entity)) {
            try {
              $existing = $exists->toUrl()->toRenderArray() + [
                '#type' => 'link',
                '#title' => $exists->label(),
              ];
            }
            catch (UndefinedLinkTemplateException $e) {
              $existing = $exists->label();
            }
            $diff = [
              '#type' => 'link',
              '#title' => $this->t('Diff'),
              '#url' => Url::fromRoute(
                'entity_pilot.arrival_approve_diff',
                [
                  'ep_arrival' => $entity->id(),
                  'passenger_id' => $passenger_id,
                ]
              ),
              '#attributes' => [
                'class' => [
                  'use-ajax',
                ],
                'data-dialog-type' => 'modal',
                'data-dialog-options' => json_encode([
                  'width' => 'auto',
                ]),
              ],
            ];
          }
          $options[$passenger_id] = [
            'id' => $unsaved_entity->uuid(),
            'label' => $unsaved_entity->label(),
            'entity_type' => $unsaved_entity->getEntityType()->getLabel(),
            'preview' => [
              'data' => [
                '#type' => 'link',
                '#title' => $this->t('Preview'),
                '#url' => Url::fromRoute(
                  'entity_pilot.arrival_approve_preview',
                  [
                    'ep_arrival' => $entity->id(),
                    'passenger_id' => $passenger_id,
                  ]
                ),
                '#attributes' => [
                  'data-dialog-type' => 'modal',
                  'class' => [
                    'use-ajax',
                  ],
                  'data-dialog-options' => json_encode([
                    'width' => '700',
                  ]),
                ],
              ],
            ],
            'existing' => ['data' => $existing],
            'diff' => ['data' => $diff],
          ];
        }

        $this->options = $options;
      }
      catch (\InvalidArgumentException $e) {
        $this->setMessage($this->t('There was a problem decrypting your content. Please check that your secret is valid in the Entity Pilot Accounts settings page.'), 'error');
      }
    }
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSelectedOptions(FieldItemListInterface $items, $delta = 0) {
    // We need to check against a flat list of options.
    $valid_ids = array_keys($this->getOptions($items->getEntity()));

    $selected_options = [];
    foreach ($items as $item) {
      $value = $item->{$this->column};
      // Keep the value if it actually is in the list of options (needs to be
      // checked against the valid list).
      if (in_array($value, $valid_ids, TRUE)) {
        $selected_options[$value] = $value;
      }
    }

    return $selected_options;
  }

}
