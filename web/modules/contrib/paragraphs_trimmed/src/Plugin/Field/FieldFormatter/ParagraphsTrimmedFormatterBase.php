<?php

namespace Drupal\paragraphs_trimmed\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\entity_reference_revisions\Plugin\Field\FieldFormatter\EntityReferenceRevisionsEntityFormatter;
use Drupal\paragraphs_trimmed\Plugin\ParagraphsTrimmedFormatterInterface;

/**
 * Base class for paragraph trimmed formatters.
 *
 * The basic idea with this base class is to render the paragraphs field as
 * html, then run it through another text field "triming" formatter. e.g. core's
 * text_trimmed formatter, or the contributed "smart_trim" formatter. The
 * trimming formatter's settings and defaults are merged with our own and then
 * we use its viewElements() method to render the output.
 *
 * Formatters extending this base class only need to implement the
 * getTrimFormatterType() method to tell us which formatter will be doing the
 * trimming.
 *
 * @see Drupal\paragraphs_trimmed\Plugin\Field\FieldFormatter\ParagraphsTrimmedFormatter
 */
abstract class ParagraphsTrimmedFormatterBase extends EntityReferenceRevisionsEntityFormatter implements ContainerFactoryPluginInterface, ParagraphsTrimmedFormatterInterface {

  /**
   * The formatter that will do the trimming.
   * @var \Drupal\Core\Field\FormatterInterface
   */
  protected $formatter = NULL;

  /**
   * Constructs a StringFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LoggerChannelFactoryInterface $logger_factory, EntityDisplayRepositoryInterface $entity_display_repository, FormatterInterface $formatter) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $logger_factory, $entity_display_repository);
    $this->formatter = $formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Create a formatter object so we can steal some settings
    $formatter = $container->get('plugin.manager.field.formatter')->createInstance(static::getTrimFormatterType(), $configuration);
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('logger.factory'),
      $container->get('entity_display.repository'),
      $formatter
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $default_settings = \Drupal::service('plugin.manager.field.formatter')->getDefaultSettings(static::getTrimFormatterType());
    return [
      'summary_field' => '',
      'format' => 'full_html',
    ] + parent::defaultSettings() + $default_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $options = [];
    foreach (filter_formats() as $filter) {
      $options[$filter->id()] = $filter->label();
    }
    $form = parent::settingsForm($form, $form_state) + [
      'summary_field' => [
        '#type' => 'select',
        '#title' => $this->t('Summary Field'),
        '#description' => $this->t('If provided, the value of this field will be used instead of the trimmed paragraphs output.'),
        '#options' => $this->getSummaryFieldOptions(),
        '#default_value' => $this->getSetting('summary_field'),
      ],
      'format' => [
        '#type' => 'select',
        '#title' => $this->t('Text Format'),
        '#description' => $this->t('Select a text format to apply to the rendered paragraphs output before trimming.'),
        '#options' => $options,
        '#default_value' => $this->getSetting('format'),
      ]
    ] + $this->formatter->settingsForm($form, $form_state);
    $form['view_mode']['#description'] = $this->t('Select the view mode to render the paragraphs. This rendered markup will then be trimmed using the following settings.');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $text_format = FilterFormat::load($this->getSetting('format'));
    if ($field_name = $this->getSetting('summary_field')) {
      $summary[] = $this->t('Summary Field: :summary_field', [':summary_field' => $this->getSummaryFieldOptions()[$field_name]]);
    }
    $summary[] = $this->t('Text Format: :formatter', [':formatter' => $text_format->label()]);

    $summary = array_merge($summary, $this->formatter->setSettings($this->getSettings())->settingsSummary());

    return $summary;
  }

  /**
   * Returns the value of the summary field.
   */
  protected function getSummaryFieldValue($items) {

    $value = '';
    if ($field_name = $this->getSetting('summary_field')) {
      $entity = $items->getEntity();
      $source_field_definition = FieldStorageConfig::loadByName($entity->getEntityTypeId(), $field_name);
      $main_property = $source_field_definition->getMainPropertyName();
      $value = $entity->{$field_name}->{$main_property};
    }

    return $value;
  }

  /**
   * Get the render element for the summary field.
   */
  protected function getSummaryFieldElement($items) {
    $value = '';
    if ($field_name = $this->getSetting('summary_field')) {
      $entity = $items->getEntity();
      // Render using the default field formatter.
      $value = $entity->{$field_name}->view(['label_display' => 'hidden']);
    }
    return $value;
  }

  /**
   * Returns the summary field options.
   *
   * We just let any field on the entity be used as a summary field.
   */
  protected function getSummaryFieldOptions() {

    $options = ['' => '- None -'];
    $entity_type_id = $this->fieldDefinition->getTargetEntityTypeId();
    $bundle = $this->fieldDefinition->getTargetBundle();
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type_id, $bundle);

    // Only show FieldConfig fields
    foreach ($fields as $field_definition) {
      if ($field_definition instanceof FieldConfigInterface) {
        $options[$field_definition->getName()] = $field_definition->getLabel();
      }
    }

    return $options;
  }

}
