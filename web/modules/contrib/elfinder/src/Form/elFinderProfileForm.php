<?php

/**
 * @file
 * Contains \Drupal\elfinder\Form\elFinderProfileForm.
 */

namespace Drupal\elfinder\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;

/**
 * Base form for elFinder Profile entities.
 */
class elFinderProfileForm extends EntityForm {

  /**
   * Folder permissions.
   *
   * @var array
   */
  public $folderPermissions;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $elfinder_profile = $this->getEntity();
    // Check duplication
    if ($this->getOperation() === 'duplicate') {
      $elfinder_profile = $elfinder_profile->createDuplicate();
      $elfinder_profile->set('label', $this->t('Duplicate of !label', array('!label' => $elfinder_profile->label())));
      $this->setEntity($elfinder_profile);
    }
    // Label
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $elfinder_profile->label(),
      '#maxlength' => 64,
      '#required' => TRUE,
      '#weight' => -20,
    );
    // Id
    $form['id'] = array(
      '#type' => 'machine_name',
      '#machine_name' => array(
        'exists' => array(get_class($elfinder_profile), 'load'),
        'source' => array('label'),
      ),
      '#default_value' => $elfinder_profile->id(),
      '#maxlength' => 32,
      '#required' => TRUE,
      '#weight' => -20,
    );
    // Description
    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $elfinder_profile->get('description'),
      '#weight' => -10,
    );
    
    $conf = array(
      '#tree' => TRUE,
    );
    
    
    $selected_roles = array();

    foreach ($elfinder_profile->getConf('roles') as $role => $value) {
      /* excluding not selected roles */
      if (!empty($value) && $value != "0" && $role == $value) {
        $selected_roles[] = $role;
      }
    }

    $conf['roles'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#default_value' => $selected_roles,
      '#options' => array(),
      '#description' => $this->t('Roles for which profile settings will be applied'),
    );
    
    foreach (user_roles() as $key => $role) {
      $conf['roles']['#options'][$key] = $role->label();
    }
 
    $conf['filesystem_settings'] = array(
      '#type' => 'fieldset',
      '#title' =>  $this->t('File system'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    $conf['volumes'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Volumes'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    
    $conf['filesystem_settings']['mimedetect'] = array(
      '#type' => 'radios',
      '#title' => $this->t('File type detection'),
      '#default_value' => $elfinder_profile->getConf('mimedetect'),
      '#options' => array(
          'auto' => $this->t('Automatical detection'),
      ),
      '#parents' => array('conf', 'mimedetect'),
    );

    if (function_exists('finfo_open')) {
        $conf['filesystem_settings']['mimedetect']['#options']['finfo'] = $this->t('php finfo');
    }

    if (function_exists('mime_content_type')) {
        $conf['filesystem_settings']['mimedetect']['#options']['php'] = $this->t('php mime_content_type()');
    }

    $conf['filesystem_settings']['mimedetect']['#options']['linux'] = $this->t('file -ib (linux)');
    $conf['filesystem_settings']['mimedetect']['#options']['bsd'] = $this->t('file -Ib (bsd)');
    $conf['filesystem_settings']['mimedetect']['#options']['internal'] = $this->t('By file extension (built-in)');
    $conf['filesystem_settings']['mimedetect']['#options']['drupal'] = $this->t('Drupal API');
    
    $conf['filesystem_settings']['file_url_type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Selected file url type'),
      '#default_value' => $elfinder_profile->getConf('file_url_type'),
      '#options' => array(
          'true' => $this->t('Absolute'),
          'false' => $this->t('Relative'),
      ),
      '#parents' => array('conf', 'file_url_type'),
    );

   $conf['filesystem_settings']['fileperm'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Created file permissions'),
      '#default_value' => $elfinder_profile->getConf('fileperm'),
      '#size' => 4,
      '#parents' => array('conf', 'fileperm'),
   );

   $conf['filesystem_settings']['dirperm'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Created directory permissions'),
      '#default_value' => $elfinder_profile->getConf('dirperm'),
      '#size' => 4,
      '#parents' => array('conf', 'dirperm'),
   );

    
    $conf['filesystem_settings']['maxfilesize'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Maximum upload size'),
      '#default_value' => $elfinder_profile->getConf('maxfilesize'),
      '#description' => $this->t('Enter a value like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes) in order to restrict the allowed file size. If left empty the file sizes will be limited only by PHP\'s maximum post and file upload sizes (current limit <strong>%limit</strong>).', array('%limit' => format_size(file_upload_max_size()))),
      '#size' => 10,
      '#weight' => 5,
      '#parents' => array('conf', 'maxfilesize')
    );

    $conf['filesystem_settings']['user_quota'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('User quota'),
      '#default_value' => $elfinder_profile->getConf('user_quota'),
      '#description' => $this->t('Enter a value like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes) in order to restrict the allowed file size. If left empty the file sizes will be unlimited.'),
      '#size' => 10,
      '#weight' => 5,
      '#parents' => array('conf', 'user_quota')
    );

    $conf['thumbnail_settings'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Thumbnails'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    $conf['thumbnail_settings']['tmbsize'] = array(
      '#type' => 'number',
      '#title' => $this->t('Thumbnail size'),
      '#default_value' => $elfinder_profile->getConf('tmbsize'),
      '#size' => 4,
      '#parents' => array('conf', 'tmbsize')
    );

    $conf['thumbnail_settings']['tmbdirname'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Thumbnail directory name'),
      '#default_value' => $elfinder_profile->getConf('tmbdirname'),
      '#size' => 10,
      '#parents' => array('conf', 'tmbdirname')
    );

    $conf['thumbnail_settings']['imglib'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Image manipulation library'),
      '#default_value' =>  $elfinder_profile->getConf('imglib'),
      '#options' => array(
          'auto' => $this->t('Automatical detection'),
          'imagick' => $this->t('Image Magick'),
          'gd' => $this->t('GD'),
      ),
      '#parents' => array('conf', 'imglib')
    );

    $conf['thumbnail_settings']['tmbcrop'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Image crop'),
      '#default_value' => $elfinder_profile->getConf('tmbcrop'),
      '#options' => array(
          'true' => $this->t('Crop image to fit thumbnail size'),
          'false' => $this->t('Scale image to fit thumbnail size'),
      ),
      '#parents' => array('conf', 'tmbcrop')
    );
    
    $conf['misc_settings'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Miscellaneous'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );

    $conf['misc_settings']['rememberlastdir'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Remember last opened directory'),
      '#default_value' => $elfinder_profile->getConf('rememberlastdir'),
      '#options' => array(
          'true' => $this->t('Yes'),
          'false' => $this->t('No'),
      ),
      '#description' => $this->t('Creates a cookie. Disable if you have issues with caching.'),
      '#parents' => array('conf', 'rememberlastdir')
    );
    
    $conf['misc_settings']['manager_width'] = array(
      '#type' => 'number',
      '#title' => $this->t('File manager width'),
      '#default_value' => $elfinder_profile->getConf('manager_width'),
      '#size' => 4,
      '#parents' => array('conf', 'manager_width')
    );

    $conf['misc_settings']['manager_height'] = array(
      '#type' => 'number',
      '#title' => $this->t('File manager height'),
      '#default_value' => $elfinder_profile->getConf('manager_height'),
      '#size' => 4,
      '#parents' => array('conf', 'manager_height')
    );

    $conf['misc_settings']['ckeditor_upload_directory'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('CKEditor Upload Path'),
      '#default_value' => $elfinder_profile->getConf('ckeditor_upload_directory'),
      '#size' => 40,
      '#description' => $this->t('Image upload path. Default file uri is used if no uri prefix specified. Examples: public://ckeditor - image will be uploaded into public://ckeditor; images/ckeditor - image will be uploaded to :uriimages/ckeditor', array(':uri' => file_build_uri(''))),
      '#parents' => array('conf', 'ckeditor_upload_directory')
    );

    $conf['volumes'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Volumes'),
      '#weight' => 10,
    );
    
    $volumes = $elfinder_profile->getConf('volumes', array());
    
    for ($i = 0; $i < count($volumes); $i++) {
     $conf['volumes'][$i] = $this->volumeForm($i, $volumes[$i]);
    }
    
    $conf['volumes'][] = $this->volumeForm(count($volumes));

    $form['conf'] = $conf;


    return parent::form($form, $form_state);
  }

  public function volumeForm($delta, array $values = array()) {
    $values += array(
        'path' => '', 
        'label' => '',
        'url' => '',
    );
    
    
    $form = array(
      '#type' => 'fieldset',
      '#attributes' => array('class' => array('folder-container')),
      '#title' => $this->t('Volume @n (@p)', array('@n' => ($delta + 1), '@p' => $values['path'])),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      
    );

    $form['path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#default_value' => isset($values['path']) ? $values['path'] : '',
      '#prefix' => '<div class="elfinder-field-wrapper-volume-path">',
      '#suffix' => '</div>',
      '#size' => 40,
    );

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#size' => 15,
      '#default_value' => isset($values['label']) ? $values['label'] : '',
      '#description' => $this->t('Root directory label in directory tree'),
    );

    $form['url'] = array(
      '#type' => 'textfield',
      '#title' => t('URL'),
      '#default_value' => isset($values['url']) ? $values['url'] : '',
      '#prefix' => '<div class="elfinder-field-wrapper-volume-path">',
      '#suffix' => '</div>',
      '#description' => $this->t('Custom URL prefix (default %def)', array('%def' => $defaulturl)),
      '#size' => 40,
    );

    
    return $form;
  }


  public function validate(array $form, FormStateInterface $form_state) {

    $volumes = array();
    foreach ($form_state->getValue(array('conf', 'volumes')) as $i => $volume) {
      $path = trim($volume['path']);

      if ($path === '') {
        continue;
      }

    }

    if (!$volumes) {
      return $form_state->setError($form['conf']['volumes'][0]['path'], $this->t('You must define a volume.'));
    }
    $form_state->setValue(array('conf', 'volumes'), array_values($volumes));
    
    return parent::validate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $elfinder_profile = $this->getEntity();
    $status = $elfinder_profile->save();
    if ($status == SAVED_NEW) {
      drupal_set_message($this->t('Profile %name has been added.', array('%name' => $elfinder_profile->label())));
    }
    elseif ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The changes have been saved.'));
    }
    $form_state->setRedirect('entity.elfinder_profile.edit_form', array('elfinder_profile' => $elfinder_profile->id()));
  }

  /**
   * Returns folder permission definitions.
   */
  public function permissionInfo() {
    return $this->folderPermissions;
  }

}
