<?php

namespace Drupal\contact_storage_export;

use Drupal\contact\MessageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\CreatedItem;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Render\RendererInterface;
use Drupal\csv_serialization\Encoder\CsvEncoder;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;
use Drupal\link\LinkItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a set of methods to export contact messages in CSV format.
 */
class ContactStorageExportService implements ContainerInjectionInterface {

  /**
   * The CSV encoder.
   *
   * @var \Drupal\csv_serialization\Encoder\CsvEncoder
   */
  protected $csvEncoder;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Creates a new ContactStorageExportService object.
   *
   * @param \Drupal\csv_serialization\Encoder\CsvEncoder $csv_encoder
   *   The CSV encoder.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(CsvEncoder $csv_encoder, RendererInterface $renderer) {
    $this->csvEncoder = $csv_encoder;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('csv_serialization.encoder.csv'),
      $container->get('renderer')
    );
  }

  /**
   * Returns a serialized message.
   *
   * @param \Drupal\contact\MessageInterface $message
   *   The message to serialize.
   * @param string[] $settings
   *   (optional) A settings array containing:
   *     - columns (A list of columns/fields to include)
   *     - date_format (Format to use with date fields)
   *     - labels (A list of columns labels)
   *
   * @return string
   *   The serialized contact message object data.
   */
  public function serialize(MessageInterface $message, array $settings = []) {
    $labels = isset($settings['labels']) ? $settings['labels'] : $this->getLabels($message);

    $all_keys = array_keys($labels);
    $selected_keys = isset($settings['columns']) ? array_keys($settings['columns']) : $all_keys;
    $excluded_keys = array_diff($all_keys, $selected_keys);
    // Exclude UUID field.
    $excluded_keys[] = 'uuid';

    $values = [];
    foreach ($message->getFields() as $field_name => $definition) {
      // Exclude marked columns.
      if (in_array($field_name, $excluded_keys)) {
        continue;
      }

      // Set the keys to readable labels and format data for CSV serialization.
      $values[$labels[$field_name]] = $this->getFormattedValue($message, $field_name, $settings);
    }

    return $values;
  }

  /**
   * Formats values of the given field.
   *
   * @param \Drupal\contact\MessageInterface $message
   *   A message entity.
   * @param string $field
   *   A field name.
   * @param array $settings
   *   A settings array.
   *
   * @return string
   *   Formatted value.
   */
  public function getFormattedValue(MessageInterface $message, $field, array $settings) {
    // Get the field storage definition and the field type.
    $storage_definition = $message->getFieldDefinition($field)->getFieldStorageDefinition();
    $type = $storage_definition->getType();
    $values = [];

    // Iterate over field items and format its values.
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    foreach ($message->$field as $item) {
      switch ($type) {
        case 'link':
          $values[] = $this->formatLink($item);
          break;

        case 'created':
        case 'daterange':
        case 'timestamp':
        case 'datetime':
          $date_format = isset($settings['date_format']) ? $settings['date_format'] : NULL;
          $values[] = $this->formatDateTime($item, $date_format);
          break;

        case 'entity_reference':
          $values[] = $this->formatEntityReference($item, $storage_definition->getMainPropertyName());
          break;

        default:
          // Display a field item value using default field formatter.
          $renderable_array = $item->view();
          $values[] = $this->renderAsString($renderable_array);
      }
    }

    return $values;
  }

  /**
   * Returns an absolute URL as a string value.
   *
   * @param \Drupal\link\LinkItemInterface $link_item
   *   The link item to format.
   *
   * @return string
   *   The URL.
   */
  protected function formatLink(LinkItemInterface $link_item) {
    return $link_item->getUrl()->setAbsolute()->toString();
  }

  /**
   * Renders a given array and returns its string representation.
   *
   * @param array $renderable_array
   *   A renderable array.
   *
   * @return string
   *   Returns a string value.
   */
  protected function renderAsString(array $renderable_array) {
    return (string) $this->renderer->renderPlain($renderable_array);
  }

  /**
   * Formats a date-time field item with a given format.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   Afield item to format.
   * @param string $format
   *   A date format. Defaults to short format (m/d/Y - H:i).
   *
   * @return string
   *   Formatted date-time value.
   */
  protected function formatDateTime(FieldItemInterface $item, $format) {
    $format = $format ?: 'short';
    $settings = [];

    if ($item instanceof CreatedItem) {
      $settings['date_format'] = $format;
    }
    elseif ($item instanceof DateRangeItem) {
      // Remove new lines between start and end dates.
      $date_range_item_view = $item->view(['settings' => ['format_type' => $format]]);
      $markup = $this->renderAsString($date_range_item_view);
      return str_replace(PHP_EOL, '', $markup);
    }
    elseif ($item instanceof DateTimeItem) {
      $settings['format_type'] = $format;
    }

    $date_item_view = $item->view(['settings' => $settings]);
    return $this->renderAsString($date_item_view);
  }

  /**
   * Formats entity reference value.
   *
   * @param \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $entity_reference_item
   *   The entity reference item.
   * @param string $property
   *   The main property.
   *
   * @return string
   *   Formatted entity reference value.
   */
  protected function formatEntityReference(EntityReferenceItem $entity_reference_item, $property) {
    // Display a label without a link.
    $display_options = [
      'type' => 'entity_reference_label',
      'settings' => ['link' => FALSE],
    ];
    if ($renderable_array = $entity_reference_item->view($display_options)) {
      return $this->renderAsString($renderable_array);
    }

    // Fallback to the main property.
    return (string) $entity_reference_item->$property;
  }

  /**
   * Returns labels from the field definitions.
   *
   * @param \Drupal\contact\MessageInterface $message
   *   A contact message object to get labels for.
   *
   * @return array
   *   The labels.
   */
  public function getLabels(MessageInterface $message) {
    $labels = [];
    foreach ($message->getFieldDefinitions() as $key => $field) {
      if ($label = $field->getLabel()) {
        // Remove characters not allowed in keys of associative arrays.
        $labels[$key] = filter_var($label, FILTER_SANITIZE_STRING,
          FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_NO_ENCODE_QUOTES
        );
      }
    }
    return $labels;
  }

  /**
   * Returns the encoded contact messages.
   *
   * @param \Drupal\contact\MessageInterface[] $messages
   *   The messages.
   * @param string[] $settings
   *   The settings array.
   * @param string $format
   *   (optional) The encoding format. Defaults to CSV.
   *
   * @return string
   *   The encoded data.
   */
  public function encode(array $messages, array $settings = [], $format = 'csv') {
    $data = [];

    // Get labels.
    if (!isset($settings['labels'])) {
      $message = reset($messages);
      $settings['labels'] = $this->getLabels($message);
    }

    // Iterate over contact message and get serialized data.
    foreach ($messages as $message) {
      $data[] = $this->serialize($message, $settings);
    }

    return $this->encodeData($data, $format);
  }

  /**
   * Returns the encoded contact messages.
   *
   * @param string[] $data
   *   A serialized contact message data.
   * @param string $format
   *   (optional) The encoding format. Defaults to CSV.
   *
   * @return string
   *   The encoded data.
   */
  public function encodeData(array $data, $format = 'csv') {
    return $this->csvEncoder->encode($data, $format);
  }

}
