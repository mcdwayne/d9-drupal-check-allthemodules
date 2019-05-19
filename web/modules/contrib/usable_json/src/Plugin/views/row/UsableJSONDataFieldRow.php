<?php

namespace Drupal\usable_json\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\rest\Plugin\views\row\DataFieldRow;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Plugin which displays fields as raw data.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "usable_json_data_field",
 *   title = @Translation("Usable JSON Fields"),
 *   help = @Translation("Use fields as row data."),
 *   display_types = {"data"}
 * )
 */
class UsableJSONDataFieldRow extends DataFieldRow {

  /**
   * Stores an array of options to determine if the raw field output is used.
   *
   * @var array
   */
  protected $normalizedOutputOptions = [];

  protected $serializer;

  /**
   * UsableJSONDataFieldRow constructor.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   Serializer.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, Serializer $serializer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('serializer'));
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    if (!empty($this->options['field_options'])) {
      $options = (array) $this->options['field_options'];
      // Prepare a trimmed version of replacement aliases.
      $aliases = static::extractFromOptionsArray('alias', $options);
      $this->replacementAliases = array_filter(array_map('trim', $aliases));
      // Prepare an array of raw output field options.
      $this->rawOutputOptions = static::extractFromOptionsArray('raw_output', $options);
      $this->normalizedOutputOptions = static::extractFromOptionsArray('normalized_output', $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['field_options'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Field'),
        $this->t('Alias'),
        $this->t('Raw output'),
        $this->t('Normalized output'),
      ],
      '#empty' => $this->t('You have no fields. Add some to your view.'),
      '#tree' => TRUE,
    ];

    $options = $this->options['field_options'];

    if ($fields = $this->view->display_handler->getOption('fields')) {
      foreach ($fields as $id => $field) {
        // Don't show the field if it has been excluded.
        if (!empty($field['exclude'])) {
          continue;
        }
        $form['field_options'][$id]['field'] = [
          '#markup' => $id,
        ];
        $form['field_options'][$id]['alias'] = [
          '#title' => $this->t('Alias for @id', ['@id' => $id]),
          '#title_display' => 'invisible',
          '#type' => 'textfield',
          '#default_value' => isset($options[$id]['alias']) ? $options[$id]['alias'] : '',
          '#element_validate' => [[$this, 'validateAliasName']],
        ];
        $form['field_options'][$id]['raw_output'] = [
          '#title' => $this->t('Raw output for @id', ['@id' => $id]),
          '#title_display' => 'invisible',
          '#type' => 'checkbox',
          '#default_value' => isset($options[$id]['raw_output']) ? $options[$id]['raw_output'] : '',
        ];
        $form['field_options'][$id]['normalized_output'] = [
          '#title' => $this->t('Raw output for @id', ['@id' => $id]),
          '#title_display' => 'invisible',
          '#type' => 'checkbox',
          '#default_value' => isset($options[$id]['normalized_output']) ? $options[$id]['normalized_output'] : '',
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    /* @var \Drupal\views\ResultRow $row */
    $output = [];

    $entity = $row->_entity;
    foreach ($this->view->field as $id => $field) {
      // If the raw output option has been set, just get the raw value.
      if (!empty($this->rawOutputOptions[$id])) {
        $value = $field->getValue($row);
      }
      // If normalized output.
      elseif (!empty($this->normalizedOutputOptions[$id])) {
        $value = $this->serializer->normalize($entity->{$id});
      }
      // Otherwise, pass this through the field advancedRender() method.
      else {
        $value = $field->advancedRender($row);
      }

      // Omit excluded fields from the rendered output.
      if (empty($field->options['exclude'])) {
        $output[$this->getFieldKeyAlias($id)] = $value;
      }
    }

    return $output;
  }
}
