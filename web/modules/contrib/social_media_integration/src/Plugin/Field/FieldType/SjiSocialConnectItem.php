<?php

/**
 * @image
 * Contains \Drupal\sjisocialconnect\Plugin\Field\FieldType\SjiSocialConnectItem.
 */

namespace Drupal\sjisocialconnect\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\image\Plugin\Field\FieldType\ImageItem as ImageItem;
use Drupal\sjisocialconnect\Controller\FacebookController as FacebookController;
use Drupal\sjisocialconnect\Controller\TwitterController as TwitterController;

/**
 * Plugin implementation of the 'sjisocialconnect' field type.
 *
 * @FieldType(
 *   id = "sjisocialconnect",
 *   label = @Translation("Sji social connect"),
 *   description = @Translation("This field stores data to send on social media."),
 *   default_widget = "sjisocialconnect",
 *   default_formatter = "sjisocialconnect",
 *   column_groups = {
 *     "file" = {
 *       "label" = @Translation("File"),
 *       "columns" = {
 *         "target_id", "width", "height"
 *       }
 *     },
 *     "alt" = {
 *       "label" = @Translation("Alt"),
 *       "translatable" = TRUE
 *     },
 *     "title" = {
 *       "label" = @Translation("Title"),
 *       "translatable" = TRUE
 *     },
 *   },
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *   constraints = {"ValidReference" = {}, "ReferenceAccess" = {}}
 * )
 */
class SjiSocialConnectItem extends FileItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
      'provider' => '',
      'message_label' => '',
      'message' => '',
      'rows' => 3,
      'max_length' => 115,
      'placeholder' => t('Message to be post on social network.'),
    ) + ImageItem::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    // @todo Get automaticly the providers.
    $settings = array(
      'provider' => '',
      'message_label' => '',
      'message' => '',
      'rows' => 3,
      'max_length' => 115,
      'facebook' => array(),
      'twitter' => array(),
      'bitly' => array(),
      'placeholder' => t('Message to be post on social network'),
      'file_directory' => 'sjisocialconnect',
    ) + ImageItem::defaultFieldSettings();
    
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = ImageItem::schema($field_definition);
    $schema['columns']['message'] = array(
      'description' => 'Message to social network.',
      'type' => 'text',
      'size' => 'big',
    );
    $schema['columns']['provider'] = array(
      'description' => 'Provider(s).',
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
    );
    
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties += ImageItem::propertyDefinitions($field_definition);
    $properties['provider'] = DataDefinition::create('string')
      ->setLabel(t('Provider(s)'))
      ->addConstraint('Length', array('max' => 255));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = array();
    // One value for this field.
    $form['#field']->cardinality = 1;

    // We need the field-level 'default_image' setting, and $this->getSettings()
    // will only provide the instance-level one, so we need to explicitly fetch
    // the field.
    $settings = $this->getFieldDefinition()->getFieldStorageDefinition()->getSettings();

    $scheme_options = \Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::WRITE_VISIBLE);
    $element['uri_scheme'] = array(
      '#type' => 'radios',
      '#title' => t('Upload destination'),
      '#options' => $scheme_options,
      '#default_value' => $settings['uri_scheme'],
      '#description' => t('Select where the final files should be stored. Private file storage has significantly more overhead than public files, but allows restricted access to files within this field.'),
    );

    // Add default_image element.
    // static::defaultImageForm($element, $settings);
    // $element['default_image']['#description'] = t('If no image is uploaded, this image will be shown on display.');
    
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    // @todo Get automaticly the providers.
    // Facebook settings (for this field only).
    $facebook = $this->getSetting('facebook');
    FacebookController::login($facebook);
    $element = FacebookController::formElement($facebook);
    // Twitter settings.
    $twitter = $this->getSetting('twitter');
    $element += TwitterController::formElement($twitter);
    
    // Get base form from FileItem.
    $element += parent::fieldSettingsForm($form, $form_state);

    $settings = $this->getSettings();

    // Add maximum and minimum resolution settings.
    $max_resolution = explode('×', $settings['max_resolution']) + array('', '');
    $element['max_resolution'] = array(
      '#type' => 'item',
      '#title' => t('Maximum image resolution'),
      '#element_validate' => array(array(get_class($this), 'validateResolution')),
      '#weight' => 4.1,
      '#field_prefix' => '<div class="container-inline">',
      '#field_suffix' => '</div>',
      '#description' => t('The maximum allowed image size expressed as WIDTH×HEIGHT (e.g. 640×480). Leave blank for no restriction. If a larger image is uploaded, it will be resized to reflect the given width and height. Resizing images on upload will cause the loss of <a href="@url">EXIF data</a> in the image.', array('@url' => 'http://en.wikipedia.org/wiki/Exchangeable_image_file_format')),
    );
    $element['max_resolution']['x'] = array(
      '#type' => 'number',
      '#title' => t('Maximum width'),
      '#title_display' => 'invisible',
      '#default_value' => $max_resolution[0],
      '#min' => 1,
      '#field_suffix' => ' × ',
    );
    $element['max_resolution']['y'] = array(
      '#type' => 'number',
      '#title' => t('Maximum height'),
      '#title_display' => 'invisible',
      '#default_value' => $max_resolution[1],
      '#min' => 1,
      '#field_suffix' => ' ' . t('pixels'),
    );

    $min_resolution = explode('×', $settings['min_resolution']) + array('', '');
    $element['min_resolution'] = array(
      '#type' => 'item',
      '#title' => t('Minimum image resolution'),
      '#element_validate' => array(array(get_class($this), 'validateResolution')),
      '#weight' => 4.2,
      '#field_prefix' => '<div class="container-inline">',
      '#field_suffix' => '</div>',
      '#description' => t('The minimum allowed image size expressed as WIDTH×HEIGHT (e.g. 640×480). Leave blank for no restriction. If a smaller image is uploaded, it will be rejected.'),
    );
    $element['min_resolution']['x'] = array(
      '#type' => 'number',
      '#title' => t('Minimum width'),
      '#title_display' => 'invisible',
      '#default_value' => $min_resolution[0],
      '#min' => 1,
      '#field_suffix' => ' × ',
    );
    $element['min_resolution']['y'] = array(
      '#type' => 'number',
      '#title' => t('Minimum height'),
      '#title_display' => 'invisible',
      '#default_value' => $min_resolution[1],
      '#min' => 1,
      '#field_suffix' => ' ' . t('pixels'),
    );

    // Remove the description option.
    unset($element['description_field']);

    // Add title and alt configuration options.
    $element['alt_field'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable <em>Alt</em> field'),
      '#default_value' => $settings['alt_field'],
      '#description' => t('The alt attribute may be used by search engines, screen readers, and when the image cannot be loaded. Enabling this field is recommended'),
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
          ':input[name="field[settings][alt_field]"]' => array('checked' => TRUE),
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
          ':input[name="field[settings][title_field]"]' => array('checked' => TRUE),
        ),
      ),
    );

    // Add default_image element.
    static::defaultImageForm($element, $settings);
    $element['default_image']['#description'] = t("If no image is uploaded, this image will be shown on display and will override the field's default image.");

    return $element;
  }
  
  /**
   * {@inheritdoc}
   */
  public function preSave() {
    ImageItem::preSave();
  }

  /**
   * {@inheritdoc}
   */
  public function update() {
    self::insert();
  }
  
  /**
   * {@inheritdoc}
   */
  public function insert() {
    $token_service = \Drupal::token();
    $field_settings = $this->getFieldDefinition()->getFieldStorageDefinition();
    $entity = $this->getEntity();
    $entity_type = $entity->getEntityTypeId();
    // $entity_type = $field_settings->getTargetEntityTypeId();
    $message = $token_service->replace($this->message, array($entity_type => $entity));
    $params = array(
      'entity_type' => $entity_type,
      'bundle' => is_object($entity) ? $entity->getType() : NULL,
      'entity_id' => is_object($entity) ? $entity->id() : NULL,
      'message' => trim(strip_tags($message)),
      'images_uri' => array($this->entity->getFileUri()),
    );
    
    // For the Facebook field send the message on Facebook.
    if ($this->provider == 'facebook' && (trim(strip_tags($message)) != '' || $this->entity->id())) {
      $params['config'] = $this->getSetting('facebook');
      FacebookController::post($params);
    }
    // For the Twitter field send the message on twitter.
    if ($this->provider == 'twitter' && (trim(strip_tags($message)) != '' || $this->entity->id())) {
      $params['config'] = $this->getSetting('twitter');
      TwitterController::post($params);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    // $settings = $field_definition->getSettings();
    $values = ImageItem::generateSampleValue($field_definition);
    // Message.
    $values['message'] = $random->paragraphs();
    return $values;
  }

  /**
   * Element validate function for resolution fields.
   */
  public static function validateResolution($element, FormStateInterface $form_state) {
    ImageItem::validateResolution($element, $form_state);
  }

  /**
   * Builds the default_image details element.
   *
   * @param array $element
   *   The form associative array passed by reference.
   * @param array $settings
   *   The field settings array.
   */
  protected function defaultImageForm(array &$element, array $settings) {
    $element['default_image'] = array(
      '#type' => 'details',
      '#title' => t('Default image'),
      '#open' => TRUE,
    );
    
    // Get Logo site.
    if (empty($settings['default_image']['fid'])) {
      $theme = \Drupal::config('system.theme');
      $default_theme = $theme->get('default'); // bartik by default.
      $logo_path_uri = theme_get_setting('logo.path', $default_theme);
      if (trim($logo_path_uri) == '') {
        $logo_url = theme_get_setting('logo.url', $default_theme);
        if (trim($logo_url) != '') {
          $file_contents = file_get_contents($logo_url);
          $file = file_save_data($file_contents, 'public://sjisocialconnect');
        }
      }
      else {
        $file_path = file_create_url($logo_path_uri);
        $file_contents = file_get_contents($file_path);
        $file = file_save_data($file_contents, 'public://sjisocialconnect');
      }
      if (is_object($file)) {
        $fid = $file->id();
        if (is_numeric($fid) && $fid > 0) {
          $settings['default_image']['fid'] = $fid;
          // Records that a module is using a file.
          // @see FileUsageInterface.
          \Drupal::service('file.usage')->add($file, 'sjisocialconnect', 'default_image', $fid);
        }
      }
    }
    
    $element['default_image']['fid'] = array(
      '#type' => 'managed_file',
      '#required' => TRUE,
      '#title' => t('Image'),
      '#description' => t('Image to be shown if no image is uploaded.'),
      // Add site logo as default image.
      '#default_value' => empty($settings['default_image']['fid']) ? array() : array($settings['default_image']['fid']),
      '#upload_location' => $settings['uri_scheme'] . '://sjisocialconnect/',
      '#element_validate' => array(
        '\Drupal\file\Element\ManagedFile::validateManagedFile',
        array(get_class($this), 'validateDefaultImageForm'),
      ),
      '#upload_validators' => $this->getUploadValidators(),
    );
    $element['default_image']['alt'] = array(
      '#type' => 'textfield',
      '#title' => t('Alternative text'),
      '#description' => t('This text will be used by screen readers, search engines, and when the image cannot be loaded.'),
      '#default_value' => $settings['default_image']['alt'],
      '#maxlength' => 512,
    );
    $element['default_image']['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#description' => t('The title attribute is used as a tooltip when the mouse hovers over the image.'),
      '#default_value' => $settings['default_image']['title'],
      '#maxlength' => 1024,
    );
    $element['default_image']['width'] = array(
      '#type' => 'value',
      '#value' => $settings['default_image']['width'],
    );
    $element['default_image']['height'] = array(
      '#type' => 'value',
      '#value' => $settings['default_image']['height'],
    );
  }

  /**
   * Validates the managed_file element for the default Image form.
   *
   * This function ensures the fid is a scalar value and not an array. It is
   * assigned as a #element_validate callback in
   * \Drupal\image\Plugin\Field\FieldType\ImageItem::defaultImageForm().
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateDefaultImageForm(array &$element, FormStateInterface $form_state) {
    ImageItem::validateDefaultImageForm($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function isDisplayed() {
    // Image items do not have per-item visibility settings.
    ImageItem::isDisplayed();
  }
  
  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    if ($max_length = $this->getSetting('max_length')) {
      $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', array(
        'provider' => array(
          'Length' => array(
            'max' => $max_length,
            'maxMessage' => t('%name: may not be longer than @max characters.', array('%name' => $this->getFieldDefinition()->getLabel(), '@max' => $max_length)),
          ),
        ),
      ));
    }

    return $constraints;
  }

}
