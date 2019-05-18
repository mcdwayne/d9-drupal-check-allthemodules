<?php

namespace Drupal\flickr_stream\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\flickr_stream\FlickrStreamApi;

/**
 * Plugin implementation of the 'FlickrStreamDefaultWidget' widget.
 *
 * @FieldWidget(
 *   id = "FlickrStreamDefaultWidget",
 *   label = @Translation("FlickrStream"),
 *   field_types = {
 *     "FlickrStream"
 *   }
 * )
 */
class FlickrStreamDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Validation user id regex.
   */
  const VALIDATION_REGEX = '~(\d+@\w+?.\d)~';

  /**
   * The flickr api service.
   *
   * @var \Drupal\flickr_stream\FlickrStreamApi
   */
  protected $flickrApi;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, FlickrStreamApi $flickrApi) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->flickrApi = $flickrApi;
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
      // Add Flickr Service.
      $container->get('flickr.stream.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $formState
  ) {

    $element['flickr_stream_user_id'] = [
      '#type' => 'textfield',
      '#title' => t('Flickr user id'),
      '#default_value' => isset($items[$delta]->flickr_stream_user_id) ? $items[$delta]->flickr_stream_user_id : NULL,
      '#empty_value' => '',
      '#required' => TRUE,
      '#placeholder' => t('Flickr user id.'),
    ];

    $element['flickr_stream_photoset_id'] = [
      '#type' => 'textfield',
      '#title' => t('Flickr album id'),
      '#default_value' => isset($items[$delta]->flickr_stream_photoset_id) ? $items[$delta]->flickr_stream_photoset_id : NULL,
      '#empty_value' => '',
      '#description' => t("If leave field empty, last photos from user's account will be grab"),
      '#placeholder' => t('Flickr album id.'),
    ];

    $element['flickr_stream_photo_count'] = [
      '#type' => 'number',
      '#title' => t('Photo count'),
      '#default_value' => isset($items[$delta]->flickr_stream_photo_count) ? $items[$delta]->flickr_stream_photo_count : NULL,
      '#empty_value' => '',
      '#min' => 1,
      '#max' => 20,
    ];

    // If cardinality is 1, ensure a label is output for the field by wrapping
    // it in a details element.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() == 1) {
      $element += [
        '#type' => 'details',
        '#open' => TRUE,
        '#attributes' => ['class' => ['container']],
        '#element_validate' => [
          [$this, 'validate'],
        ],
      ];
    }
    // Add validate to fields input data.
    $element += ['#element_validate' => [[$this, 'validate']]];

    return $element;
  }

  /**
   * Validate the fields.
   *
   * @param array $element
   *   A form element array containing basic properties for the widget.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validate(array $element, FormStateInterface $form_state) {
    if (!preg_match_all(self::VALIDATION_REGEX, $element['flickr_stream_user_id']['#value'])) {
      $form_state->setErrorByName('flickr_stream_user_id',
        $this->t('Invalid Flickr User ID, please check it.'));
    }
    if (!empty($element['flickr_stream_photoset_id']['#value']) && !is_numeric($element['flickr_stream_photoset_id']['#value'])) {
      $form_state->setErrorByName('flickr_stream_photoset_id',
        $this->t('Invalid Flickr Album ID, please check it. <br>Must contain only digits'));
    }

    // Check API errors.
    if (!$form_state->hasAnyErrors()) {
      $flickr_conf = $this->flickrApi->setConfig(
        $element['flickr_stream_user_id']['#value'],
        $element['flickr_stream_photoset_id']['#value'],
        $element['flickr_stream_photo_count']['#value']
      );
      if (!empty($element['flickr_stream_photoset_id']['#value'])) {
        $response = $this->flickrApi->getAlbumPhotos($flickr_conf);
      }
      else {
        $response = $this->flickrApi->getUserPhotos($flickr_conf);
      }
      if (isset($response['stat']) && $response['stat'] == 'fail') {
        $form_state->setErrorByName('Flickr block api error',
          $response['message'] .
          ' [' . $response['code'] . ']');
      }
    }
  }

}
