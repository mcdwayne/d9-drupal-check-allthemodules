<?php
/**
 * @file
 *   Contains Drupal\remote_image\Plugin\Field\FieldType\RemoteImageField.
 *
 * @todo: evaluate if we need to differentiate between remote and internal links types.
 */

namespace Drupal\remote_image\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\link\Plugin\Field\FieldType\LinkItem;

/**
 * Plugin implementation of the 'remote_image' field type.
 *
 * @FieldType(
 *   id = "remote_image",
 *   label = @Translation("Remote Image"),
 *   description = @Translation("Provides an external link as a field with some meta-data."),
 *   category = @Translation("Reference"),
 *   default_widget = "remote_image",
 *   default_formatter = "remote_image",
 *   constraints = {"LinkType" = {}, "LinkAccess" = {}, "LinkExternalProtocols" = {}, "LinkNotExistingInternal" = {}}
 * )
 */
class RemoteImageItem extends LinkItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    // Add image settings to the default link settings.
    return [
      'alt_field' => 1,
      'alt_field_required' => 0,
      'title_field' => 1,
      'title_field_required' => 0,
    ] + array_diff_key(parent::defaultFieldSettings(), ['title' => FALSE]);
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    // Get base form from LinkItem.
    $element = array_diff_key(parent::fieldSettingsForm($form, $form_state), ['title' => FALSE]);
    $element['link_type']['#weight'] = 8;
    $settings = $this->getSettings();

    // Add title and alt configuration options.
    $element['alt_field'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable <em>Alt</em> field'),
      '#default_value' => $settings['alt_field'],
      '#description' => t('The alt attribute may be used by search engines, screen readers, and when the image cannot be loaded. Enabling this field is recommended.'),
      '#weight' => 9,
    );
    $element['alt_field_required'] = array(
      '#type' => 'checkbox',
      '#title' => t('<em>Alt</em> field required'),
      '#default_value' => $settings['alt_field_required'],
      '#description' => t('Making this field required is recommended.'),
      '#weight' => 10,
      '#states' => array(
        'visible' => array(
          ':input[name="settings[alt_field]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $element['title_field'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable <em>Title</em> field'),
      '#default_value' => $settings['title_field'],
      '#description' => t('The title attribute is used as a tooltip when the mouse hovers over the image. Enabling this field is not recommended as it can cause problems with screen readers.'),
      '#weight' => 11,
    );
    $element['title_field_required'] = array(
      '#type' => 'checkbox',
      '#title' => t('<em>Title</em> field required'),
      '#default_value' => $settings['title_field_required'],
      '#weight' => 12,
      '#states' => array(
        'visible' => array(
          ':input[name="settings[title_field]"]' => array('checked' => TRUE),
        ),
      ),
    );

    return $element;
  }

  /**
   * Builds the default_image details element.
   *
   * @param array $element
   *   The form associative array passed by reference.
   * @param array $settings
   *   The field settings array.
   */
  protected function defaultImageForm(array &$element, array $settings) {// Add the default image form element.
    // @todo Add url validation @see \Drupal\link\Plugin\Field\FieldWidget\LinkWidget.
    // @todo Add autocomplete for internal urls @see \Drupal\link\Plugin\Field\FieldWidget\LinkWidget.
    // @todo Figure out why default width and height won't save.
    $element['default_image'] = [
      '#type' => 'details',
      '#title' => t('Default image'),
      '#open' => TRUE,
      'uri' => [
        '#type' => 'url',
        '#title' => $this->t('Default image URL'),
        '#default_value' => $settings['default_image']['uri'],
        '#maxlength' => 2048,
        '#description' => $this->t('The URL of the remote image.'),
      ],
      'alt' => [
        '#type' => 'textfield',
        '#title' => $this->t('Alternative text'),
        '#description' => $this->t('This text will be used by screen readers, search engines, and when the image cannot be loaded.'),
        '#default_value' => $settings['default_image']['alt'],
        '#maxlength' => 512,
      ],
      'title' => [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#description' => t('The title attribute is used as a tooltip when the mouse hovers over the image.'),
        '#default_value' => $settings['default_image']['title'],
        '#maxlength' => 1024,
      ],
      'width' => [
        '#type' => 'number',
        '#title' => $this->t('Width'),
        '#description' => t('The width of the image'),
        '#value' => $settings['default_image']['width'],
      ],
      'height' => [
        '#type' => 'number',
        '#title' => $this->t('Height'),
        '#description' => t('The height of the image.'),
        '#value' => $settings['default_image']['height'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    return [
      'alt' => DataDefinition::create('string')
        ->setLabel(t('Alternative text'))
        ->setDescription(t("Alternative image text, for the image's 'alt' attribute.")),
      'title' => DataDefinition::create('string')
        ->setLabel(t('Title'))
        ->setDescription(t("Image title text, for the image's 'title' attribute.")),
      'width' => DataDefinition::create('integer')
        ->setLabel(t('Width'))
        ->setDescription(t('The width of the image in pixels.')),
      'height' => DataDefinition::create('integer')
        ->setLabel(t('Height'))
        ->setDescription(t('The height of the image in pixels.')),
    ] + parent::propertyDefinitions($field_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns'] = [
      'alt' => array(
        'description' => "Alternative image text, for the image's 'alt' attribute.",
        'type' => 'varchar',
        'length' => 512,
      ),
      'title' => array(
        'description' => "Image title text, for the image's 'title' attribute.",
        'type' => 'varchar',
        'length' => 1024,
      ),
      'width' => array(
        'description' => 'The width of the image in pixels.',
        'type' => 'int',
        'unsigned' => TRUE,
      ),
      'height' => array(
        'description' => 'The height of the image in pixels.',
        'type' => 'int',
        'unsigned' => TRUE,
      ),
    ] + $schema['columns'];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // @todo: respect the remote image settings here.
    // Generate random dimensions.
    $width = mt_rand(100, 1024);
    $height = mt_rand(100, 1024);

    // Use \Drupal\Component\Utility\Random.
    $random = new Random();

    return [
      'uri' => "http://placehold.it/{$width}x{$height}",
      'alt' => $random->sentences(4),
      'title' => $random->sentences(4),
      'width' => $width,
      'height' => $height,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('uri')->getValue();
    return $value === NULL || $value === '';
  }

}
