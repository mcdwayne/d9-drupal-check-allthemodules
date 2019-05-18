<?php

namespace Drupal\fillpdf_test\Plugin\BackendService;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\fillpdf\Plugin\BackendServiceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @BackendService(
 *   id = "test",
 *   label = @Translation("FillPDF Test Backend Service"),
 * )
 */
class Test extends BackendServiceBase implements ContainerFactoryPluginInterface {

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configuration = $configuration;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function parse($pdf_content) {
    return static::getParseResult();
  }

  /**
   * {@inheritdoc}
   */
  public function merge($pdf_content, array $field_mappings, array $options) {
    $this->state->set('fillpdf_test.last_populated_metadata', [
      'field_mapping' => $field_mappings,
      'options' => $options,
    ]);

    return file_get_contents(drupal_get_path('module', 'fillpdf_test') . '/files/fillpdf_test_v3.pdf');
  }

  /**
   * Returns a list of fields, as if a PDF file was parsed.
   *
   * Note that there is a duplicate field that get consolidated in
   * InputHelper::attachPdfToForm() at the latest.
   * The expected number of fields is therefore three, not four.
   *
   * @return array
   *   List of associative arrays representing fields.
   *
   * @see \Drupal\fillpdf\InputHelper::attachPdfToForm()
   */
  public static function getParseResult() {
    return [
      0 => [
        'name' => 'ImageField',
        'value' => '',
        'type' => 'Pushbutton',
      ],
      1 => [
        'name' => 'TestButton',
        'value' => '',
        'type' => 'Pushbutton',
      ],
      2 => [
        'name' => 'TextField1',
        'value' => '',
        'type' => 'Text',
      ],
      3 => [
        'name' => 'TextField2',
        'value' => '',
        'type' => 'Text',
      ],
      4 => [
        'name' => 'ImageField',
        'value' => '',
        'type' => 'Pushbutton',
      ],
    ];
  }

}
