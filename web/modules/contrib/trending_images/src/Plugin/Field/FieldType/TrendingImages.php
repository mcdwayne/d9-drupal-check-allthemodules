<?php

namespace Drupal\trending_images\Plugin\Field\FieldType;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\file\Entity\File;
use Drupal\trending_images\TrendingImagesManagerInterface;

/**
* @FieldType(
*   id = "trending_images",
*   label = @Translation("Trending images"),
*   description = @Translation("This field stores trending images from social networks and displays them."),
*   default_widget = "trending_images_widget",
*   default_formatter = "trending_images_formatter"
* )
*/
class TrendingImages extends FieldItemBase implements FieldItemInterface {

  /**
   * Return entity loaded field image
   * */
  public function getImage(){
    $image = File::load($this->get('target_id')->getValue());
    return $image;
  }

  /**
  * {@inheritdoc}
  */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['target_id'] = DataDefinition::create('integer')
      ->setLabel(t('Image file ID Reference'))
      ->setDescription(t('The ID of the referenced image.'))
      ->setSetting('unsigned', TRUE);

    $properties['width'] = DataDefinition::create('integer')
      ->setLabel(t('Width'))
      ->setDescription(t('The width of the image in pixels.'));

    $properties['height'] = DataDefinition::create('integer')
      ->setLabel(t('Height'))
      ->setDescription(t('The height of the image in pixels.'));

    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Social network'))
      ->setDescription(t('Social network channel.'));

    $properties['source_link'] = DataDefinition::create('string')
      ->setLabel(t('Source link'))
      ->setDescription(t('A link to which image will point to.'));

    $properties['description'] = DataDefinition::create('string')
      ->setLabel(t('Photo description'))
      ->setDescription(t('Description of a photo.'));

    $properties['permanent'] = DataDefinition::create('boolean')
      ->setLabel(t('Permanent image'))
      ->setDescription(t('Make image permanently stay in field.'));

    $properties['likes'] = DataDefinition::create('string')
      ->setLabel(t('Likes'))
      ->setDescription(t('Number of likes on instagram.'));

    $properties['comments'] = DataDefinition::create('string')
      ->setLabel(t('Comments'))
      ->setDescription(t('Number of comments on instagram.'));


    return $properties;
  }

  public static function getColumns() {
    return $columns = array(
      'target_id' => array(
        'description' => 'The ID of the referenced image.',
        'type' => 'int',
        'unsigned' => TRUE,
      ),
      'width' => [
        'description' => 'The width of the image in pixels.',
        'type' => 'int',
        'unsigned' => TRUE,
      ],
      'height' => [
        'description' => 'The height of the image in pixels.',
        'type' => 'int',
        'unsigned' => TRUE,
      ],
      'value' => array(
        'description' => 'Social network channel.',
        'type' => 'varchar',
        'length' => 50,
      ),
      'source_link' => array(
        'description' => 'A link to which image will point to.',
        'type' => 'varchar',
        'length' => 255,
      ),
      'description' => array(
        'description' => 'Description of a photo.',
        'type' => 'varchar',
        'length' => 255,
      ),
      'permanent' => array(
        'description' => 'Make image permanently stay in field.',
        'type' => 'int',
        'size' => 'tiny',
        'default' => 0,
      ),
      'likes' => array(
        'description' => 'Number of likes on instagram.',
        'type' => 'varchar',
        'length' => 255,
      ),
      'comments' => array(
        'description' => 'Number of comments on instagram.',
        'type' => 'varchar',
        'length' => 255,
      ),
    );
  }

  /**
  * {@inheritdoc}
  */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    $columns = TrendingImages::getColumns();

    $foreign_keys = array('target_id' => [
      'table' => 'file_managed',
      'columns' => ['target_id' => 'fid'],
    ]);

    $schema = array(
      'columns' => $columns,
      'indexes' => array(),
      'foreign keys' => $foreign_keys,
    );

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('target_id')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
        'file_directory' => '/trendingImages/',
        'upload_radios' => 'public',
        'file_extensions' => 'png gif jpg jpeg',
        'max_filesize' => '',
        'max_resolution_x' => '',
        'max_resolution_y' => '',
        'interval' => '4',
        'providers' => []
      ] + parent::defaultFieldSettings();
  }

  /** TODO: CHECK TAG VALIDITY
   *  TODO: CHECK DATA VALIDITY
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $field_settings = $this->getSettings();

    $element = [];
    $element['file_directory'] = [
      '#type' => 'textfield',
      '#title' => t('File directory'),
      '#default_value' => $this->getSetting('file_directory'),
      '#description' => t('Optional subdirectory within the upload destination where files will be stored. Do not include preceding or trailing slashes.'),
      '#element_validate' => [[get_class($this), 'validateDirectory']],
    ];

    $element['upload_radios'] = [
      '#title' => $this->t('Upload location'),
      '#type' => 'radios',
      '#default_value' => $this->getSetting('upload_radios'),
      '#options' => array(
        'public' => t('Public'),
        'private' => t('Private')
      )];

    // Make the extension list a little more human-friendly by comma-separation.
    $extensions = str_replace(' ', ', ', $this->getSetting('file_extensions'));
    $element['file_extensions'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed file extensions'),
      '#default_value' => $extensions,
      '#description' => t('Separate extensions with a space or comma and do not include the leading dot.'),
      '#element_validate' => [[get_class($this), 'validateExtensions']],
      '#maxlength' => 256,
      // By making this field required, we prevent a potential security issue
      // that would allow files of any type to be uploaded.
      '#required' => TRUE,
    ];

    $element['max_filesize'] = [
      '#type' => 'textfield',
      '#title' => t('Maximum upload size'),
      '#default_value' => $this->getSetting('max_filesize'),
      '#description' => t('Enter a value like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes) in order to restrict the allowed file size. If left empty the file sizes will be limited only by PHP\'s maximum post and file upload sizes (current limit <strong>%limit</strong>).', ['%limit' => format_size(file_upload_max_size())]),
      '#size' => 10,
      '#element_validate' => [[get_class($this), 'validateMaxFilesize']],
    ];

    $element['max_resolution_x'] = [
      '#type' => 'number',
      '#title' => t('Maximum width (pixels)'),
      '#default_value' => $this->getSetting('max_resolution_x'),
      '#min' => 1,
    ];
    $element['max_resolution_y'] = [
      '#type' => 'number',
      '#title' => t('Maximum height (pixels)'),
      '#default_value' => $this->getSetting('max_resolution_y'),
      '#min' => 1,
    ];

    $element['interval'] = [
      '#title' => $this->t('Interval between image update from social networks (in hours)'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('interval'),
    ];

    $element['providers'] = [
      '#type' => 'details',
      '#title' => $this->t('Image sources'),
      '#description' => $this->t('Specify here where to pull the images from.'),
      '#open' => TRUE,
    ];

    $definitions = $this->getProviderPluginManager()->getDefinitions();
    foreach ($definitions as $plugin_id => $definition) {
      $plugin = $this->getProviderPluginManager()->createInstance($plugin_id, $this->getProviderSettings($plugin_id));

      $html_id = Html::getId(__FUNCTION__ . '-plugin-' . $plugin_id);

      $element['providers'][$plugin_id] = [
        '#theme_wrappers' => ['container'],
        '#attributes' => ['id' => $html_id],
      ];
      $element['providers'][$plugin_id]['enable'] = [
        '#type' => 'checkbox',
        '#title' => $definition['label'],
        '#default_value' => isset($field_settings['providers'][$plugin_id]['enable']) ? $field_settings['providers'][$plugin_id]['enable'] : FALSE,
        '#ajax' => [
          'callback' => [self::class, 'providerAjax'],
          'wrapper' => $html_id,
        ],
      ];

      $is_enabled = $element['providers'][$plugin_id]['enable']['#default_value'];
      if ($form_state->hasValue(['settings', 'providers', $plugin_id, 'enable'])) {
        $is_enabled = $form_state->getValue(['settings', 'providers', $plugin_id, 'enable']);
      }

      if ($is_enabled && $plugin instanceof PluginFormInterface) {
        $element['providers'][$plugin_id]['plugin_configuration'] = [];
        $sub_form_state = SubformState::createForSubform($element['providers'][$plugin_id]['plugin_configuration'], $form, $form_state);
        $element['providers'][$plugin_id]['plugin_configuration'] = $plugin->buildConfigurationForm($element['providers'][$plugin_id]['plugin_configuration'], $sub_form_state);
      }
    }

    return $element;
  }

  /**
   * Retrieves the upload validators for a file field.
   *
   * @return array
   *   An array suitable for passing to file_save_upload() or the file field
   *   element's '#upload_validators' property.
   */
  public function getUploadValidators() {
    $validators = [];
    $settings = $this->getSettings();

    // Cap the upload size according to the PHP limit.
    $max_filesize = Bytes::toInt(file_upload_max_size());
    if (!empty($settings['max_filesize'])) {
      $max_filesize = min($max_filesize, Bytes::toInt($settings['max_filesize']));
    }

    // There is always a file size limit due to the PHP server limit.
    $validators['file_validate_size'] = [$max_filesize];

    // Add the extension check if necessary.
    if (!empty($settings['file_extensions'])) {
      $validators['file_validate_extensions'] = [$settings['file_extensions']];
    }

    if(!empty($settings['max_resolution_x']) && !empty($settings['max_resolution_y'])){
      $validators['trending_images_validate_resolution'] = [$settings['max_resolution_x'], $settings['max_resolution_y']];
    }

    return $validators;
  }

  /**
   * Form API callback.
   *
   * This function is assigned as an #element_validate callback in
   * fieldSettingsForm().
   *
   * This doubles as a convenience clean-up function and a validation routine.
   * Commas are allowed by the end-user, but ultimately the value will be stored
   * as a space-separated list for compatibility with file_validate_extensions().
   */
  public static function validateExtensions($element, FormStateInterface $form_state) {
    if (!empty($element['#value'])) {
      $extensions = preg_replace('/([, ]+\.?)/', ' ', trim(strtolower($element['#value'])));
      $extensions = array_filter(explode(' ', $extensions));
      $extensions = implode(' ', array_unique($extensions));
      if (!preg_match('/^([a-z0-9]+([.][a-z0-9])* ?)+$/', $extensions)) {
        $form_state->setError($element, t('The list of allowed extensions is not valid, be sure to exclude leading dots and to separate extensions with a comma or space.'));
      }
      else {
        $form_state->setValueForElement($element, $extensions);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();
    // Determine the dimensions.
    if($this->get('target_id')->getValue()){
      $file = File::load($this->get('target_id')->getValue());
      $size = getimagesize($file->getFileUri());

      $this->width = $size[0];
      $this->height = $size[1];
    }
    else {
      trigger_error(sprintf("Missing image ID on field %s.", $this->get('target_id')->getValue()), E_USER_WARNING);
    }
  }

  /**
   * Goes trough field channel configuration and fetches images depending on it.
   * Calls API and returns array of data ready to be saved.
   * */
  public static function fetchTrendingImages(array $fieldSettings, $amount){
    $trendingFeed = [];
    /** @var \Drupal\trending_images\TrendingImagesManager $type */
    $type = \Drupal::service('plugin.manager.social_channel');

    // Create plugin instances and fetch images from networks
    foreach ($fieldSettings as $channel){
      $instaPlugin = $type->createInstance($channel['source'], $channel);
      $trendingFeed[] = $instaPlugin->getSocialNetworkFeed($amount, $fieldSettings, 100000);
    }

    // Reformat the array for easier extraction
    foreach ($trendingFeed as $imagesFromChannel){
      foreach($imagesFromChannel as $image){
        $feed[] = $image;
      }
    }
    return $feed;
  }

  /**
   * Ajax callback for image provider checkboxes.
   */
  public static function providerAjax($form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#parents'];
    array_pop($parents);

    $sub_form = NestedArray::getValue($form, $parents);
    return $sub_form;
  }

  /**
   * Retrieve config for a given provider from the field settings.
   *
   * @param string $plugin_id
   *   Plugin ID of the image provider whose config to retrieve
   *
   * @return array
   *   Config of the requested image provider
   */
  protected function getProviderSettings($plugin_id) {
    $settings = $this->getSettings();
    return isset($settings['providers'][$plugin_id]['plugin_configuration']) ? $settings['providers'][$plugin_id]['plugin_configuration'] : [];
  }

  /**
   * Get plugin manager that oversees image provider plugins.
   *
   * @return TrendingImagesManagerInterface
   */
  protected function getProviderPluginManager() {
    return \Drupal::service('plugin.manager.social_channel');
  }

}
