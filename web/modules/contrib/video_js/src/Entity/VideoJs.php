<?php

namespace Drupal\video_js\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\file\Entity\File;


/**
 * Defines the Video JS entity.
 *
 * @ContentEntityType(
 *   id = "video_js",
 *   label = @Translation("Video JS"),
 *   bundle_label = @Translation("Video JS type"),
 *   handlers = {
 *     "list_builder" = "Drupal\video_js\VideoJsListBuilder",
 *     "form" = {
 *       "default" = "Drupal\video_js\Form\VideoJsForm",
 *       "add" = "Drupal\video_js\Form\VideoJsForm",
 *       "edit" = "Drupal\video_js\Form\VideoJsForm",
 *       "delete" = "Drupal\video_js\Form\VideoJsDeleteForm"
 *     },
 *   },
 *   base_table = "video_js",
 *   admin_permission = "administer video js",
 *   entity_keys = {
 *     "id" = "pid",
 *     "label" = "label",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/user-interface/video_js/pages/{video_js}",
 *     "add-form" = "/admin/config/user-interface/video_js/pages/add",
 *     "edit-form" = "/admin/config/user-interface/video_js/pages/{video_js}/edit",
 *     "delete-form" = "/admin/config/user-interface/video_js/pages/{video_js}/delete",
 *   }
 * )
 */
class VideoJs extends ContentEntityBase implements VideoJsInterface {

  /**
   * The Video JS ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Video JS label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Video JS path.
   *
   * @var string
   */
  protected $path;

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->get('paths')->getvalue()[0]['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setPath($path) {
    $this->path = $path;
    return $this;
  }

  /**
   * The VideoJs Source label.
   *
   * @var string
   */
  protected $type = self::TYPE_FILE;

  /**
   * Get source type.
   *
   * @return string
   *   Source type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Get Link
   *
   * @return string
   *   link to file.
   */
  public function getLink() {
    return $this->get('link')->getValue();
  }

  /**
   * Get the file path.
   *
   * @return string
   *   File path
   */
  public function getFile() {
    return $this->get('file')->getValue()[0]['target_id'];
  }

  /**
   * Get the file format.
   *
   * @return string
   *   File format.
   */
  public function getFormat() {
    return $this->get('format')->getValue()[0]['value'];
  }

  /**
   * Create the links to the video
   *
   * @return array
   */
  public function createVideoLinks() {

    if ($this->getType() == static::TYPE_FILE) {
      $file = File::load($this->getFile());
      return file_create_url($file->getFileUri());
    }

    return $this->getLink();
  }

  /**
   * Get the target element.
   *
   * @return string
   *   File format.
   */
  public function getElement() {
    return $this->get('element')->getValue()[0]['value'];
  }

  /**
   * Get the video html.
   *
   * @return array
   *   Html render array.
   */
  public function getHtml() {
    $renderer = \Drupal::service('renderer');
    $render_array = [
      '#theme' => 'video_js__video',
      '#source' => $this->createVideoLinks(),
      '#format' => $this->getFormat()
    ];
    return $renderer->renderPlain($render_array);
  }



  /**
   * Sets the source language.
   *
   * @param string $language
   *   Language code.
   */
  public function setLanguage($language) {
    $this->set('language', $language);
  }


  /**
   * Sets the source created datetime.
   *
   * @param int $datetime
   *   The redirect created datetime.
   */
  public function setCreated($datetime) {
    $this->set('created', $datetime);
  }

  /**
   * Gets the redirect created datetime.
   *
   * @return int
   *   The redirect created datetime.
   */
  public function getCreated() {
    return $this->get('created')->value;
  }


  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    $fields['pid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Id'))
      ->setDescription(t('The id for this source.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setDescription(t('The label for this page.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'textfield',
        'weight' => 1,
        'settings' => array(
          'format' => 'plain_text'
        )
      ))
      ->setTranslatable(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The source UUID.'))
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code.'))
      ->setDisplayOptions('form', array(
        'type' => 'language_select',
        'weight' => 2,
      ));

//    https://drupal.stackexchange.com/questions/210610/why-doesnt-my-custom-entity-work-in-an-entity-reference-field

    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Type'))
      ->setDescription(t('The type of file access being used.'))
      ->setSettings([
        'allowed_values' => ['file' => 'File', 'link' => 'Link']
      ])
      ->setRequired(TRUE)
      ->setCardinality(1)
      ->setDisplayOptions('form', array(
        'type' => 'options_buttons',
        'weight' => 2,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['format'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Video format'))
      ->setDescription(t('The video format being used.'))
      ->setSettings([
        'allowed_values' => ['mp4' => 'MP4', 'webm' => 'WebM']
      ])
      ->setRequired(TRUE)
      ->setCardinality(1)
      ->setDisplayOptions('form', array(
        'type' => 'options_buttons',
        'weight' => 3,
      ));

    $validators = array(
      'file_validate_extensions' => array('mp4 webm'),
      'file_validate_size' => array(file_upload_max_size()),
    );

    $fields['file'] = BaseFieldDefinition::create('file')
      ->setLabel(t('File'))
      ->setDescription(t('Uploaded file'))
      ->setSetting('upload_validators', $validators)
      ->setSetting('file_extensions', 'mp4 webm')
      ->setDisplayOptions('form', array(
        'type' => 'file',
        'settings' => array(
          'upload_validators' => $validators,
        ),
        'weight' => 4,
      ));

    $fields['link'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Link'))
      ->setDescription(t('The link to the video being used.'))
      ->setDisplayOptions('form', array(
        'type' => 'link',
        'weight' => 5,
      ));

    $fields['paths'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Paths (not aliases)'))
      ->setDescription(t('Enter paths separated by new lines. Ensure that there are no spaces before or after paths.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string_long',
        'weight' => 3,
      ));

    $fields['element'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Target Element'))
      ->setDescription(t('The target element within which to place the video.  This element must have height and width for the video to work.'))
      ->setDefaultValue('body')
      ->setDisplayOptions('form', array(
        'type' => 'link',
        'weight' => 5,
      ));

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Enabled'))
      ->setDescription(t('Whether or not the source is enabled.'))
      ->setCardinality(1)
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'weight' => 3,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The date when the source was created.'));

    return $fields;
  }

}
