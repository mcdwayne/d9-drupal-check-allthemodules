<?php

namespace Drupal\dam\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\dam\FileDirectoryInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

/**
 * Defines the FileDirectory entity.
 *
 * @ingroup file_directory
 *
 * @ContentEntityType(
 *   id = "file_directory",
 *   label = @Translation("File Directory"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\dam\Controller\FileDirectoryListBuilder",
 *     "views_data" = "Drupal\dam\Entity\FileDirectoryViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\dam\Form\FileDirectoryForm",
 *       "add" = "Drupal\dam\Form\FileDirectoryForm",
 *       "edit" = "Drupal\dam\Form\FileDirectoryForm",
 *       "delete" = "Drupal\dam\Form\FileDirectoryDeleteForm",
 *     },
 *     "access" = "Drupal\dam\FileDirectoryAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\dam\FileDirectoryHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "file_directory",
 *   revision_table = "file_directory_revision",
 *   admin_permission = "administer file_directory entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/dam/file_directory/{file_directory}",
 *     "add-form" = "/admin/dam/file_directory/file_directory/add",
 *     "edit-form" = "/admin/dam/file_directory/{file_directory}/edit",
 *     "delete-form" = "/admin/dam/file_directory/{file_directory}/delete",
 *     "collection" = "/admin/dam/file_directory",
 *   },
 *   field_ui_base_route = "file_directory.settings"
 * )
 */

class FileDirectory extends ContentEntityBase implements FileDirectoryInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If no owner has been set explicitly, make the current user the owner.
    if (!$this->getOwner()) {
      $this->setOwnerId(\Drupal::currentUser()->id());
    }
    // If no revision author has been set explicitly, make the node owner the
    // revision author.
    if (!$this->getRevisionAuthor()) {
      $this->setRevisionAuthorId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
  * {*inheritdoc}
  */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionAuthor() {
    return $this->get('revision_uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionAuthorId($uid) {
    $this->set('revision_uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionLog() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionLog($revision_log) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the File Directory entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the File Directory entity.'))
      ->setReadOnly(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the File Directory entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Path'))
      ->setDescription(t('Set path to the folder'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['label'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('File Label'))
      ->setDescription(t('The file label of the File Label entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'file_label')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -4,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['thumbnail'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Thumbnail'))
      ->setDescription(t('Set thumbnail for the folder.'))
      ->setSettings([
        'file_directory' => 'dam',
        'alt_field_required' => TRUE,
        'file_extensions' => 'png jpg jpeg',
      ])
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'default',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'label' => 'hidden',
        'type' => 'image_image',
        'weight' => -4,
        'settings' => [
          'alt_field' => TRUE,
          'alt_field_required' => TRUE,
          'title_field' => TRUE,
          'title_field_required' => TRUE,
        ],
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['trash'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Trash'))
      ->setDescription(t('Indicates that folder is in the junk folder or not.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setSettings(['on_label' => 'Yes', 'off_label' => 'No'])
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'boolean',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the File Directory entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Updated'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revision user ID'))
      ->setDescription(t('The user ID of the author of the current revision.'))
      ->setSetting('target_type', 'user')
      ->setRevisionable(TRUE);

    $fields['directory'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Directory'))
      ->setDescription(t('The child directories of this directory entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'file_directory')
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -4,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * Checks the physical folder on the server exist.
   *
   * @param string $path
   *   Path to the folder.
   *
   * @return bool
   *   Returns the status of the folder.
   */
  public static function updateDirectory($path, FileDirectory $directory = NULL) {

    // Check if entity exists for this directory.
    $config = \Drupal::config('dam.ftp_settings');
    $query = \Drupal::entityQuery('file_directory')
      ->condition('path', str_replace($config->get('dam_root_folder'), '/', $path));
    $ids = $query->execute();
    $ids = [];
    if ($id = array_shift($ids)) {
      $entity = entity_load('file_directory', $id);
    } else {
      $correctedUri = str_replace($config->get('dam_root_folder'), '/', $path);
      $entity = FileDirectory::create([
        'name' => $config->get('dam_root_folder') == $path ? '/' : str_replace($config->get('dam_root_folder'), '', $path),
        'path' => $config->get('dam_root_folder') == $path ? '/' : str_replace($config->get('dam_root_folder'), '', $path),
      ]);
      if ($directory) {
        $entity->set('directory', [ 'target_id', $directory->id() ]);
      }
      $entity->save();
    }

    $files = glob($path . "/*");
    foreach($files as $file) {
     //check to see if the file is a folder/directory
     if(is_dir($file)) {
      FileDirectory::updateDirectory($file, $entity);
     }
     else {
        $dest = 'dam:/'; // Or whatever
        $filepath = $dest . str_replace($config->get('dam_root_folder'), '', $file);
        // $ids = \Drupal::entityQuery('file')->condition('uri', )
        $ids = \Drupal::entityQuery('file')
          ->condition('uri', $filepath)
          // ->condition('directory', $entity->id())
          ->execute();

        if ( count($ids) < 1 ) {
          $fileEntity = File::create([
            'uri' => $filepath,
            'uid' => 1,
            'status' => FILE_STATUS_PERMANENT,
          ]);

          if ($fileEntity) {
            $fileEntity->status = FILE_STATUS_PERMANENT;
            $fileEntity->directory = [ ['target_id' => $entity->id()] ];
            $fileEntity->save();
          }

        }
      }
    }
  }

  /**
  * Creates a tree for the provided default path.
  */
  public static function getTree($path, $currentPath, $showFiles = TRUE, FileDirectory $directory = NULL, &$filesList = [], &$directoryList = []) {
    $config = \Drupal::config('dam.ftp_settings');
    if ($directory == NULL) {
      $ids = \Drupal::entityQuery('file_directory')
        ->condition('path', str_replace($config->get('dam_root_folder'), '/', $path))
        ->execute();
      $directory = entity_load('file_directory', array_shift($ids));
    }

    $labelColor = ($directory->get('label')->first() !== null) ? $directory->get('label')->first()->entity->color->value : 'transparent';
    $labelName = ($directory->get('label')->first() !== null) ? $directory->get('label')->first()->entity->getName() : '';


    $tree = [
      'text' => $config->get('dam_root_folder') == $path ? '/' : str_replace($config->get('dam_root_folder'), '', $path),
      'nodes' => [],
      'href' => 'file_directory-' . $directory->id(),
      'tags' => [],
      'icon' => "glyphicon glyphicon-folder-open",
      'selectedIcon' => "glyphicon glyphicon-folder-open",
      'levels' => 2,
      'state' => [
        'expanded' => FALSE,
        // 'selected' => ($path == $currentPath),
      ],
      'backColor' => "#FFFFFF", // Use labels to determin this....
      'color' => "#000000",  // Opposite of the label color...
      'thumbnail' => FileDirectory::getThumbnail($directory),
      'info' => [
        'name' => $directory->label(),
        'created' => $directory->getCreatedTime(),
        'owner' => $directory->getOwner()->label(),
        'changed' => $directory->changed->value,
        'changed_by' => $directory->getRevisionAuthor()->label(),
        'type' => 'Folder',
        'size' => '',
        'label' => $directory->get('label')->getValue()[0]['target_id'],
        'label_name' => $labelName,
        'download' =>  $url
      ]
    ];
    $ids = \Drupal::entityQuery('file_directory')
      ->condition('path', $path)
      ->execute();

    $entity = entity_load('file_directory', array_shift($ids));

    // Get the files for this directory first.
    if ($showFiles) {
      $fileIds = \Drupal::entityQuery('file')
        ->condition('directory', $directory->id())
        ->execute();
      $files = entity_load_multiple('file', $fileIds);

      foreach($files as $file) {
        $labelColor = ($file->get('label')->first() !== null) ? $file->get('label')->first()->entity->color->value : 'transparent';
        $labelName = ($file->get('label')->first() !== null) ? $file->get('label')->first()->entity->getName() : '';
        $url = $file->url();

        $tree['nodes'][] = [
          'text' => $file->label(),
          'href' => 'file-' . $file->id(),
          'tags' => [ $labelName ],
          'icon' => "glyphicon glyphicon-folder-open",
          'selectedIcon' => "glyphicon glyphicon-folder-open",
          'state' => [
            'checked' => FALSE,
            'disabled' => FALSE,
            'expanded' => FALSE,
            'selected' => FALSE
          ],
          'nodes' => [],
          'backColor' => $labelColor, // Use labels to determin this....
          'color' => "#000000",  // Opposite of the label color...
          'thumbnail' => FileDirectory::getThumbnail($file),
          'info' => [
            'name' => $file->label(),
            'created' => $file->created->value,
            'owner' => $file->getOwner()->label(),
            'type' => $file->filemime->value,
            'size' => $file->filesize->value,
            'label' => $file->get('label')->getValue()[0]['target_id'],
            'label_name' => $labelName,
            'download' =>  $url,
          ]
        ];
      }
    }

    // Next do the same for directories:
    $directoryIds = \Drupal::entityQuery('file_directory')->condition('directory', $directory->id())->execute();
    foreach(entity_load_multiple('file_directory', $directoryIds) as $delta => $sub_directory) {
      $tree['nodes'][] = FileDirectory::getTree($sub_directory->path->value, $currentPath, $showFiles, $sub_directory);
    }

    return $tree;
  }

  /**
  * Returns the thumbnail for the file or directory.
  */
  public static function getThumbnail($file) {
    return 'https://pbs.twimg.com/profile_images/843409912201850880/00rSAecS_bigger.jpg'; // @TODO update
  }

  /**
   * Create Zip of the folder.
   *
   * @param string $path
   *   Path to the folder.
   *
   * @return string
   *   Returns path to the Zip folder.
   */
  public function createZip($path) {
    $dirName = pathinfo($path);
    $dirName = $dirName['basename'];
    // Get real path for our folder.
    $rootPath = \Drupal::service('file_system')->realpath($path);

    if (!class_exists('ZipArchive')) {
      throw new \Exception('Requires the "zip" PHP extension to be installed and enabled in order to export the site as a package.');
      return FALSE;
    }

    // Initialize archive object.
    $zip = new \ZipArchive();
    // Get files directory path.
    $filesPath = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $zip->open($filesPath . '/' . $dirName . '.zip', \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

    // Create recursive directory iterator.
    $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($rootPath),
        \RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
      // Skip directories (they would be added automatically)
      if (!$file->isDir()) {
        // Get real and relative path for current file.
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($rootPath) + 1);

        // Add current file to archive.
        $zip->addFile($filePath, $relativePath);
      }
    }

    // Zip archive will be created only after closing object.
    $zip->close();

    $zipPath = $filesPath . '/' . $dirName . '.zip';
    return \Drupal::service('file_system')->realpath($zipPath);
  }

  /**
   * Create Zip of the folder and send it in an email.
   *
   * @param string $path
   *   Path to the folder.
   * @param string $email_id
   *   Email address.
   */
  public function sendEmail($path, $email_id) {
    // Create ZIP.
    $zipPath = $this->createZip($path);
    // Send email.
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'dam';
    $key = 'dam_mail';
    $params['message'] = $this->t('Download ZIP here @var', ['@var' => $zipPath]);
    $params['subject'] = '';
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;

    $result = $mailManager->mail($module, $key, $email_id, $langcode, $params, NULL, $send);
    if ($result['result'] != true) {
      $message = t('There was a problem sending your email notification to @email.', array('@email' => $email_id));
      drupal_set_message($message, 'error');
      \Drupal::logger('dam')->error($message);
      return;
    }

    $message = t('An email notification has been sent to @email ', array('@email' => $email_id));
    drupal_set_message($message);
    \Drupal::logger('dam')->notice($message);
  }
}
