<?php

namespace Drupal\perspective\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\perspective\AnalyzeToxicityService;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextareaWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'perspective_text' widget.
 *
 * @FieldWidget(
 *   id = "perspective_text",
 *   module = "perspective",
 *   label = @Translation("Text"),
 *   field_types = {
 *     "perspective"
 *   }
 * )
 */
class TextWidget extends StringTextareaWidget implements ContainerFactoryPluginInterface {

  /**
   * Variable that will store the service.
   *
   * @var \Drupal\perspective\AnalyzeToxicityService
   */
  protected $analyzeToxicityService;

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AnalyzeToxicityService $analyzeToxicityService, ConfigFactoryInterface $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->analyzeToxicityService = $analyzeToxicityService;
    $this->configFactory = $config_factory;
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
      $container->get('perspective.analyze_toxicity'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $main_widget = parent::formElement($items, $delta, $element, $form, $form_state);
    $base_type = $main_widget['value']['#type'];
    $config = $this->configFactory->get('perspective.settings');

    $element = [
      '#type' => 'text_format',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : '',
      '#format' => $items[$delta]->format,
      '#base_type' => $base_type,
      '#attributes' => [
        'class' => ['field-perspective'],
      ],
      '#element_validate' => [
        [
          $this,
          'validate',
        ],
      ],
    ] + $main_widget['value'];

    if ($config->get('perspective.use_ajax')) {
      $element['#attached']['library'][] = 'perspective/perspective';
      $element['#attached']['drupalSettings']['perspective']['tolerance'] = $config->get('perspective.tolerance');
      $element['#attached']['drupalSettings']['perspective']['message'] = $config->get('perspective.error_text');
    }

    return $element;
  }

  /**
   * Validate the color text field.
   */
  public function validate($element, FormStateInterface $form_state) {
    $text = isset($element['#value']) ? strip_tags($element['#value']) : '';
    $config = $this->configFactory->get('perspective.settings');

    $perspectiveApiResponse = [
      'score' => $this->analyzeToxicityService->getTextToxicity($text),
    ];

    if ($perspectiveApiResponse['score'] > $config->get('perspective.tolerance')) {
      $form_state->setError($element, $config->get('perspective.error_text'));
    }
  }

}
