<?php

namespace Drupal\google_vision\Tests;

use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\simpletest\WebTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Language\LanguageInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\comment\Entity\CommentType;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;

/**
 * Base class for Google Vision API tests.
 */
abstract class GoogleVisionTestBase extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'google_vision',
    'google_vision_test',
    'image',
    'field_ui',
    'field',
  ];

  /**
   * Retrieves the entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Create a new image field.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $entity_type
   *   The entity type that this field will be added to.
   * @param string $type_name
   *   The node type that this field will be added to.
   * @param array $storage_settings
   *   A list of field storage settings that will be added to the defaults.
   * @param array $field_settings
   *   A list of instance settings that will be added to the instance defaults.
   * @param array $widget_settings
   *   Widget settings to be added to the widget defaults.
   * @param array $formatter_settings
   *   Formatter settings to be added to the formatter defaults.
   * @param string $description
   *   A description for the field.
   */
  public function createImageField($name, $entity_type, $type_name, $storage_settings = [], $field_settings = [], $widget_settings = [], $formatter_settings = [], $description = '') {
    FieldStorageConfig::create([
      'field_name' => $name,
      'entity_type' => $entity_type,
      'type' => 'image',
      'settings' => $storage_settings,
      'cardinality' => 1,
    ])->save();

    $field_config = FieldConfig::create([
      'field_name' => $name,
      'label' => $name,
      'entity_type' => $entity_type,
      'bundle' => $type_name,
      'settings' => $field_settings,
      'description' => $description,
    ]);
    $field_config->addConstraint('SafeSearch')
      ->addConstraint('UserEmotion')
      ->save();

    $form_display = $this->entityTypeManager
      ->getStorage('entity_form_display')
      ->load($entity_type . '.' . $type_name . '.' . 'default');
    if (!$form_display) {
      $values = [
        'targetEntityType' => $entity_type,
        'bundle' => $type_name,
        'mode' => 'default',
        'status' => TRUE,
      ];
      $form_display = $this->entityTypeManager
        ->getStorage('entity_form_display')
        ->create($values);
    }
    $form_display->setComponent($name, [
      'type' => 'image_image',
      'settings' => $widget_settings,
    ])->save();

    $display = $this->entityTypeManager
      ->getStorage('entity_view_display')
      ->load($entity_type . '.' . $type_name . '.' . 'default');

    if (!$display) {
      $values = [
        'targetEntityType' => $entity_type,
        'bundle' => $type_name,
        'mode' => 'default',
        'status' => TRUE,
      ];
      $display = $this->entityTypeManager
        ->getStorage('entity_view_display')
        ->create($values);
    }
    $display->setComponent($name, [
      'type' => 'image',
      'settings' => $formatter_settings,
    ])->save();

    return $field_config;
  }

  /**
   * Get the field id of the image field formed.
   *
   * @param string $entity_type.
   *  The entity type the field will be added to.
   * @param string $type.
   *  The node type the field will be added to.
   * @return string $field_id.
   *  The field id of the created field.
   */
  public function getImageFieldId($entity_type, $type) {
    // Create an image field and add an field to the custom content type.
    $storage_settings['default_image'] = [
      'uuid' => 1,
      'alt' => '',
      'title' => '',
      'width' => 0,
      'height' => 0,
    ];
    $field_settings['default_image'] = [
      'uuid' => 1,
      'alt' => '',
      'title' => '',
      'width' => 0,
      'height' => 0,
    ];
    $widget_settings = [
      'preview_image_style' => 'medium',
    ];
    $field = $this->createImageField('images', $entity_type, $type, $storage_settings, $field_settings, $widget_settings);

    // Get the field id and return it.
    $field_id = $field->id();
    return $field_id;
  }

  /**
   * Uploads an image file and saves it.
   *
   * @param integer $count
   *  The index for the image file.
   *
   * @return integer
   *  The file id of the newly created image file.
   */
  public function uploadImageFile($count = 0) {
    $images = $this->drupalGetTestFiles('image');
    $edit = [
      'files[upload]' => \Drupal::service('file_system')->realpath($images[$count]->uri),
    ];
    $this->drupalPostForm('file/add', $edit, t('Next'));
    $this->drupalPostForm(NULL, array(), t('Next'));
    $this->drupalPostForm(NULL, array(), t('Save'));
    // Get the file id of the created file and return it.
    $query = \Drupal::database()->select('file_managed', 'fm');
    $query->addExpression('MAX(fid)');
    return (int) $query->execute()->fetchField();
  }

  /**
   * Create a node of type test_images and also upload an image.
   */
  public function createNodeWithImage() {
    //Get an image.
    $images = $this->drupalGetTestFiles('image');

    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'files[images_0]' => \Drupal::service('file_system')->realpath($images[0]->uri),
    ];

    $this->drupalPostForm('node/add/test_images', $edit, t('Save and publish'));

    // Add alt text.
    $this->drupalPostForm(NULL, ['images[0][alt]' => $this->randomMachineName()], t('Save and publish'));

    // Retrieve ID of the newly created node from the current URL.
    $matches = [];
    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    return isset($matches[1]) ? $matches[1] : FALSE;
  }

  /**
   * Creates a comment comment type (bundle).
   *
   * @param string $label
   *   The comment type label.
   *
   * @return \Drupal\comment\Entity\CommentType
   *   Created comment type.
   */
  public function createCommentType($label) {
    $bundle = CommentType::create([
      'id' => $label,
      'label' => $label,
      'description' => '',
      'target_entity_type_id' => 'node',
    ]);
    $bundle->save();
    return $bundle;
  }

  /**
   * Adds the default comment field to an entity.
   *
   * Attaches a comment field named 'comment' to the given entity type and
   * bundle. Largely replicates the default behavior in Drupal 7 and earlier.
   *
   * @param string $entity_type
   *   The entity type to attach the default comment field to.
   * @param string $bundle
   *   The bundle to attach the default comment field to.
   * @param string $field_name
   *   (optional) Field name to use for the comment field. Defaults to
   *     'comment'.
   * @param int $default_value
   *   (optional) Default value, one of CommentItemInterface::HIDDEN,
   *   CommentItemInterface::OPEN, CommentItemInterface::CLOSED. Defaults to
   *   CommentItemInterface::OPEN.
   * @param string $comment_type_id
   *   (optional) ID of comment type to use. Defaults to 'comment'.
   */
  public function addCommentField($entity_type, $bundle, $field_name = 'comment', $default_value = CommentItemInterface::OPEN, $comment_type_id = 'test_comment') {
    FieldStorageConfig::create([
      'entity_type' => $entity_type,
      'field_name' => $field_name,
      'type' => 'comment',
      'translatable' => TRUE,
      'settings' => [
        'comment_type' => $comment_type_id,
      ],
    ])->save();

    $field_config = FieldConfig::create([
      'label' => 'Test Comments',
      'description' => '',
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'required' => 1,
      'default_value' => [
        [
          'status' => $default_value,
          'cid' => 0,
          'last_comment_name' => '',
          'last_comment_timestamp' => 0,
          'last_comment_uid' => 0,
        ],
      ],
    ]);
    $field_config->save();

    $form_display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load($entity_type . '.' . $bundle . '.' . 'default');
    if (!$form_display) {
      $values = [
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => 'default',
        'status' => TRUE,
      ];
      $form_display = \Drupal::entityTypeManager()
        ->getStorage('entity_form_display')
        ->create($values);
    }
    $form_display->setComponent($field_name, [
      'type' => 'comment_default',
      'weight' => 20,
    ])->save();

    $display = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load($entity_type . '.' . $bundle . '.' . 'default');
    if (!$display) {
      $values = [
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => 'default',
        'status' => TRUE,
      ];
      $display = \Drupal::entityTypeManager()
        ->getStorage('entity_view_display')
        ->create($values);
    }
    $display->setComponent($field_name, [
      'label' => 'above',
      'type' => 'comment_default',
      'weight' => 20,
    ])->save();
  }

  /**
   * Create the comment with image field.
   *
   * @param integer $nid
   *   The id of the node.
   */
  public function createCommentWithImage($nid) {
    $images = $this->drupalGetTestFiles('image');

    $edit = [
      'subject[0][value]' => $this->randomMachineName(),
      'files[images_0]' => \Drupal::service('file_system')->realpath($images[0]->uri),
    ];
    $this->drupalPostForm('node/' . $nid, $edit, t('Save'));
    $this->drupalPostForm(NULL, ['images[0][alt]' => $this->randomMachineName()], t('Save'));
  }

  /**
   * Creates a new field for referencing taxonomy vocabulary.
   *
   * @param \Drupal\taxonomy\Entity\Vocabulary $vocabulary .
   *  The vocabulary.
   */
  public function createEntityReferenceField($vocabulary) {
    $entity_type = 'taxonomy_term';
    $field_name = 'field_labels';
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'file',
      'translatable' => FALSE,
      'settings' => array(
        'target_type' => $entity_type,
      ),
      'type' => 'entity_reference',
      'cardinality' => 1,
    ));
    $field_storage->save();
    $field = FieldConfig::create(array(
      'field_storage' => $field_storage,
      'entity_type' => 'file',
      'bundle' => 'image',
      'settings' => array(
        'handler' => 'default',
        'handler_settings' => array(
          // Restrict selection of terms to a single vocabulary.
          'target_bundles' => array(
            $vocabulary->id() => $vocabulary->id(),
          ),
        ),
      ),
    ));
    $field->save();
  }

  /**
   * Creates and returns a new vocabulary.
   *
   * @param string $name.
   *  The name for the created vocabulary.
   *
   * @param string $vid.
   *  The vocabulary id.
   *
   * @return \Drupal\taxonomy\Entity\Vocabulary $vocabulary.
   *  The vocabulary.
   */
  public function createTaxonomyVocabulary($name, $vid) {
    $vocabulary = Vocabulary::create([
      'name' => $name,
      'description' => t('Stores the dominant color of the images.'),
      'vid' => $vid,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $vocabulary->save();
    return $vocabulary;
  }

  /**
   * Creates a user with profile picture attached.
   *
   * @return string $edit['name'].
   *  The user name of the newly created user.
   */
  public function createUserWithProfilePicture() {
    //Get an image.
    $images = $this->drupalGetTestFiles('image');
    $user = 'user1';
    $pass = 'password1';
    $edit = [
      'mail' => 'user@user.com',
      'name' => $user,
      'pass[pass1]' => $pass,
      'pass[pass2]' => $pass,
      'files[images_0]' => \Drupal::service('file_system')->realpath($images[0]->uri),
    ];
    $this->drupalPostForm(Url::fromRoute('user.admin_create'), $edit, t('Create new account'));
    $re_edit = [
      'pass[pass1]' => $pass,
      'pass[pass2]' => $pass,
      'images[0][alt]' => $this->randomMachineName(),
    ];
    $this->drupalPostForm(NULL, $re_edit, t('Create new account'));

    return $edit['name'];
  }
}
