<?php

namespace Drupal\fillpdf_test\Plugin\FillPdfBackend;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\fillpdf\FillPdfBackendPluginInterface;
use Drupal\fillpdf\FillPdfFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Plugin(
 *   id = "test",
 *   label = @Translation("Pass-through plugin for testing")
 * )
 */
class TestFillPdfBackend implements FillPdfBackendPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The plugin's configuration.
   *
   * @var array
   */
  protected $configuration;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a LocalFillPdfBackend plugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StateInterface $state) {
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
  public function parse(FillPdfFormInterface $fillpdf_form) {
    return static::getParseResult();
  }

  /**
   * {@inheritdoc}
   */
  public function populateWithFieldData(FillPdfFormInterface $pdf_form, array $field_mapping, array $context) {
    // Not really populated, but that isn't our job.
    $populated_pdf = file_get_contents(drupal_get_path('module', 'fillpdf_test') . '/files/fillpdf_test_v3.pdf');

    $this->state->set('fillpdf_test.last_populated_metadata', [
      'field_mapping' => $field_mapping,
      'context' => $context,
    ]);

    return $populated_pdf;
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
