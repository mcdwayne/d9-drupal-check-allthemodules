<?php

namespace Drupal\bridtv\Plugin\Field\FieldWidget;

use Drupal\bridtv\BridApiConsumer;
use Drupal\bridtv\BridInfoNegotiator;
use Drupal\bridtv\BridSerialization;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Video ID widget for Brid.TV videos.
 *
 * @FieldWidget(
 *   id = "bridtv_id",
 *   module = "bridtv",
 *   label = @Translation("Direct video ID input"),
 *   field_types = {
 *     "bridtv"
 *   }
 * )
 */
class BridtvIdWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The Brid.TV API consumer service.
   *
   * @var \Drupal\bridtv\BridApiConsumer
   */
  protected $consumer;

  /**
   * The negotiator service.
   *
   * @var \Drupal\bridtv\BridInfoNegotiator
   */
  protected $negotiator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $consumer = $container->get('bridtv.consumer');
    $negotiator = $container->get('bridtv.negotiator');
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $consumer,
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
   * @param \Drupal\bridtv\BridApiConsumer $brid_api
   *   The Brid.TV API service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, BridApiConsumer $brid_api, BridInfoNegotiator $negotiator) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->consumer = $brid_api;
    $this->negotiator = $negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $values = $items->get($delta)->getValue();
    $element['video_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Video ID'),
      '#description' => !empty($values['video_id']) ? $this->t('You cannot change the video ID, as it might be used by others already.') : $this->t('The Video ID can be found inside the Url of the video when logged in at cms.brid.tv.<br/>Go to the videos section and view the detail page of your desired video.<br/>Example: <em>cms.brid.tv/videos/edit/<b>321</b></em> would mean that <b>321</b> is the actual video ID.'),
      '#default_value' => !empty($values['video_id']) ? $values['video_id'] : NULL,
      '#disabled' => !empty($values['video_id']),
    ];
    $element['player'] = [
      '#type' => 'select',
      '#title' => $this->t('Player'),
      '#options' => ['_use_default' => $this->t('- Use default -')] + $this->negotiator->getPlayersListOptions(),
      '#default_value' => !empty($values['player']) ? $values['player'] : '_use_default',
      '#description' => $this->t('Choose the player to use as default, when not specified otherwise.'),
    ];
    $element['#element_validate'][] = [get_class($this), 'validateElement'];
    return $element;
  }

  /**
   * Form validation handler for widget elements.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();
    $field = NULL;
    foreach ($entity->getFieldDefinitions() as $definition) {
      if ($definition->getType() == 'bridtv') {
        $field = $definition->getName();
        break;
      }
    }
    $id_as_input = (int) $element['video_id']['#value'];
    if (!$id_as_input) {
      $form_state->setError($element['video_id'], t('No valid number is given.'));
    }
    /** @var \Drupal\bridtv\BridApiConsumer $consumer */
    $consumer = \Drupal::service('bridtv.consumer');
    if ($consumer->isReady()) {
      if ($field) {
        $previous_id_value = !$entity->get($field)->isEmpty() ? $entity->get($field)->first()->get('video_id')->getValue() : NULL;
        if (!($id_as_input == $previous_id_value)) {
          $storage = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId());
          $query = $storage->getQuery();
          $existing = $query->condition($field . '.video_id', $id_as_input)->range(0, 1)->execute();
          if (!empty($existing)) {
            $existing = reset($existing);
            $existing = $storage->load($existing);
            $form_state->setError($element['video_id'], t('There is already an <a href=":existing" target="_blank">existing @type entity</a> for the requested video Id.', [':existing' => $existing->toUrl()->toString(), '@type' => $entity->getEntityType()->getLabel()]));
          }
        }
      }
      $data = $consumer->fetchVideoData($id_as_input);
      if (empty($data)) {
        $form_state->setError($element['video_id'], t('No video was found at Brid.TV for the requested Id.'));
      }
    }
    else {
      $form_state->setError($element['video_id'], t('The Brid.TV API is not available. The Id thus cannot be matched to an existing video. Please contact the site administrator.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $massaged = [];
    foreach ($values as $value) {
      if (!empty($value['video_id'])) {
        $data = $this->consumer->fetchVideoData($value['video_id']);
        if (!empty($data)) {
          $decoded = BridSerialization::decode($data);
          $player_id = NULL;
          if (!empty($value['player'])) {
            $players = $this->negotiator->getPlayersListOptions();
            if (isset($players[$value['player']])) {
              $player_id = $value['player'];
            }
          }
          $massaged[] = [
            'video_id' => $value['video_id'],
            'title' => !empty($decoded['Video']['name']) ? $decoded['Video']['name'] : NULL,
            'description' => !empty($decoded['Video']['description']) ? $decoded['Video']['description'] : NULL,
            'publish_date' => !empty($decoded['Video']['publish']) ? $decoded['Video']['publish'] : NULL,
            'player' => $player_id,
            'data' => $data,
          ];
        }
      }
    }
    return $massaged;
  }

}
