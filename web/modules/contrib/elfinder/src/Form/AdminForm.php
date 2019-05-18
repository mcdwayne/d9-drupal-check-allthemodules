<?php

/**
 * @file
 * file manager admin settings page
 */

namespace Drupal\elfinder\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AdminForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return array('elfinder.settings');
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'elfinder_admin_settings';
  }

  public function elfinder_admin_profile_links($profile_name) {
    $links = l(t('Edit'), 'admin/config/media/elfinder/profile/' . $profile_name . '/edit') . ' ' . l(t('Delete'), 'admin/config/media/elfinder/profile/' . $profile_name . '/delete');
    return $links;
  }

  
  /**
   * Settings form definition
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('elfinder.settings');

    global $language;
    $user = \Drupal::currentUser();
    $path = drupal_get_path('module', 'elfinder');

    $langCode = isset($language->language) ? $language->language : 'en';

    $roles = user_roles();

    $form['filesystem_settings'] = array(
        '#type' => 'fieldset',
        '#title' => t('File system settings'),   //return parent::buildForm($form, $form_state);
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
    );


    $form['filesystem_settings']['filesystem_public_root_label'] = array(
        '#prefix' => '<div class="custom-container">',
        '#suffix' => '</div>',
        '#type' => 'textfield',
        '#title' => t('Public files root directory label'),
        '#default_value' => $config->get('filesystem.public_root_label'),
        '#description' => t('Root directory label in directory tree'),
    );

    $form['filesystem_settings']['filesystem_private_root_label'] = array(
        '#prefix' => '<div class="custom-container">',
        '#suffix' => '</div>',
        '#type' => 'textfield',
        '#title' => t('Private files root directory label'),
        '#default_value' => $config->get('filesystem.private_root_label'),
        '#description' => t('Root directory label in directory tree'),
    );

    $form['filesystem_settings']['filesystem_unmanaged_root_label'] = array(
        '#prefix' => '<div class="custom-container">',
        '#suffix' => '</div>',
        '#type' => 'textfield',
        '#title' => t('Unmanaged files root directory label'),
        '#default_value' => $config->get('filesystem.unmanaged_root_label'),
        '#description' => t('Root directory label in directory tree'),
    );

    $form['filesystem_settings']['filesystem_root_custom'] = array(
        '#prefix' => '<div class="custom-container">',
        '#suffix' => '</div>',
        '#type' => 'textfield',
        '#title' => t('Custom unmanaged files path'),
        '#default_value' => $config->get('filesystem.root_custom'),
        '#description' => t('Custom filesystem root path.') . '<br/>' . t('Available tokens: <code>%files</code> (base path, eg: <code>/</code>), <code>%name</code> (current username, eg: <code>@u</code>, <b>NOTE:</b> it is not unique - users can have same username, so better to combine it with user id value), <code>%uid</code> (current user id, eg: <code>@uid</code>), <code>%lang</code> (current language code, eg: <code>@lang</code>), plus all tokens provided by token module', array('@u' => $user->getUsername(), '@uid' => $user->id(), '@lang' => $langCode)),
    );

    $form['filesystem_settings']['filesystem_url_custom'] = array(
        '#prefix' => '<div class="custom-container">',
        '#suffix' => '</div>',
        '#type' => 'textfield',
        '#title' => t('Custom unmanaged files URL'),
        '#default_value' => $config->get('filesystem.url_custom'),
        '#description' => t('Custom filesystem url.') . '<br/>' . t('Available tokens: <code>%files</code> (base path, eg: <code>/</code>), <code>%name</code> (current username, eg: <code>@u</code>, <b>NOTE:</b> it is not unique - users can have same username, so better to combine it with user id value), <code>%uid</code> (current user id, eg: <code>@uid</code>), <code>%lang</code> (current language code, eg: <code>@lang</code>), plus all tokens provided by token module', array('@u' => $user->getUsername(), '@uid' => $user->id(), '@lang' => $langCode)),
    );

    $form['filesystem_settings']['mime_detect'] = array(
        '#type' => 'radios',
        '#title' => t('File type detection'),
        '#default_value' => $config->get('filesystem.mimedetect'),
        '#options' => array(
            'auto' => t('Automatical detection'),
        ),
    );

    $form['filesystem_settings']['filesystem_allowed_extensions'] = array(
        '#prefix' => '<div class="custom-container">',
        '#suffix' => '</div>',
        '#type' => 'textfield',
        '#title' => t('Allowed file extensions'),
        '#default_value' => $config->get('filesystem.allowed_extensions'),
        '#description' => t('Separate extensions with a space or comma and do not include the leading dot.'),
    );

    if (function_exists('finfo_open')) {
      $form['filesystem_settings']['mime_detect']['#options']['finfo'] = t('php finfo');
    }

    if (function_exists('mime_content_type')) {
      $form['filesystem_settings']['mime_detect']['#options']['php'] = t('php mime_content_type()');
    }

    $form['filesystem_settings']['mime_detect']['#options']['linux'] = t('file -ib (linux)');
    $form['filesystem_settings']['mime_detect']['#options']['bsd'] = t('file -Ib (bsd)');
    $form['filesystem_settings']['mime_detect']['#options']['internal'] = t('By file extension (built-in)');
    $form['filesystem_settings']['mime_detect']['#options']['drupal'] = t('Drupal API');

    $form['filesystem_settings']['file_url_type'] = array(
        '#type' => 'radios',
        '#title' => t('Selected file url type'),
        '#default_value' => $config->get('filesystem.fileurl'),
        '#options' => array(
            'true' => t('Absolute'),
            'false' => t('Relative'),
        ),
    );

    $form['filesystem_settings']['file_perm'] = array(
        '#type' => 'textfield',
        '#title' => t('Created file permissions'),
        '#default_value' => $config->get('filesystem.fileperm'),
        '#size' => 4,
    );

    $form['filesystem_settings']['dir_perm'] = array(
        '#type' => 'textfield',
        '#title' => t('Created directory permissions'),
        '#default_value' => $config->get('filesystem.dirperm'),
        '#size' => 4,
    );


    $form['filesystem_settings']['max_filesize'] = array(
        '#type' => 'textfield',
        '#title' => t('Maximum upload size'),
        '#default_value' => $config->get('filesystem.maxfilesize'),
        '#description' => t('Enter a value like "512" (bytes), "80 KB" (kilobytes) or "50 MB" (megabytes) in order to restrict the allowed file size. If left empty the file sizes will be limited only by PHP\'s maximum post and file upload sizes (current limit <strong>%limit</strong>).', array('%limit' => format_size(file_upload_max_size()))),
        '#size' => 10,
        '#weight' => 5,
    );

    $form['filesystem_settings']['max_filecount'] = array(
        '#type' => 'textfield',
        '#title' => t('Maximum folder size'),
        '#default_value' => $config->get('filesystem.maxfilecount'),
        '#description' => t('The maximum number of files allowed in a directory. 0 for unlimited.'),
        '#size' => 4,
        '#weight' => 5,
    );
    

    $form['filesystem_settings']['handleprivate'] = array(
        '#type' => 'radios',
        '#title' => t('Handle private downloads'),
        '#default_value' => $config->get('filesystem.handleprivate'),
        '#options' => array(
            'true' => t('Yes'),
            'false' => t('No'),
        ),
        '#description' => t('Use elFinder to handle private file downloads'),
    );

    $form['thumbnail_settings'] = array(
        '#type' => 'fieldset',
        '#title' => t('Image settings'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
    );

    $form['thumbnail_settings']['tmbsize'] = array(
        '#type' => 'textfield',
        '#title' => t('Thumbnail size'),
        '#default_value' => $config->get('thumbnail.size'),
        '#size' => 4,
    );

    $form['thumbnail_settings']['tmbdirname'] = array(
        '#type' => 'textfield',
        '#title' => t('Thumbnail directory name'),
        '#default_value' => $config->get('thumbnail.dirname'),
        '#size' => 10,
    );

    $form['thumbnail_settings']['imglib'] = array(
        '#type' => 'radios',
        '#title' => t('Image manipulation library'),
        '#default_value' => $config->get('thumbnail.imglib'),
        '#options' => array(
            'auto' => t('Automatical detection'),
            'imagick' => t('Image Magick'),
            'gd' => t('GD'),
        ),
    );

    $form['thumbnail_settings']['tmbcrop'] = array(
        '#type' => 'radios',
        '#title' => t('Image crop'),
        '#default_value' => $config->get('thumbnail.tmbcrop'),
        '#options' => array(
            'true' => t('Yes'),
            'false' => t('No'),
        ),
        '#description' => t('Crop image to fit thumbnail size. Yes - crop, No - scale image to fit thumbnail size.'),
    );

    $form['misc_settings'] = array(
        '#type' => 'fieldset',
        '#title' => t('Miscellaneous settings'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
    );

    $form['misc_settings']['rememberlastdir'] = array(
        '#type' => 'radios',
        '#title' => t('Remember last opened directory'),
        '#default_value' => $config->get('misc.rememberlastdir'),
        '#options' => array(
            'true' => t('Yes'),
            'false' => t('No'),
        ),
        '#description' => t('Creates a cookie. Disable if you have issues with caching.'),
    );

    $form['misc_settings']['usesystemjquery'] = array(
        '#type' => 'radios',
        '#title' => t('Use system jQuery'),
        '#default_value' => $config->get('misc.usesystemjquery'),
        '#options' => array(
            'true' => t('Yes'),
            'false' => t('No'),
        ),
        '#description' => t('Use system jQuery and jQuery UI when possible. If set to \'No\' jQuery hosted at Google will be uses.'),
    );

    $form['misc_settings']['manager_width'] = array(
        '#type' => 'textfield',
        '#title' => t('File manager width'),
        '#default_value' => $config->get('misc.manager_width'),
        '#size' => 4,
    );

    $form['misc_settings']['manager_height'] = array(
        '#type' => 'textfield',
        '#title' => t('File manager height'),
        '#default_value' => $config->get('misc.manager_height'),
        '#size' => 4,
    );



    return parent::buildForm($form, $form_state);
  }

  /**
   * Save form data
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->configFactory->get('forum.settings');

    $config->set('thumbnail_size', $form_state['values']['tmbsize']);
    $config->set('thumbnail_dirname', $form_state['values']['tmbdirname']);

    if ($form_state['values']['filesystem_root_custom'] != '') {
      $config->set('filesystem.root_custom', $form_state['values']['filesystem_root_custom']);
    }

    $config->set('filesystem.url_custom', $form_state['values']['filesystem_url_custom']);
    $config->set('filesystem.mimedetect', $form_state['values']['mime_detect']);
    $config->set('filesystem.fileurl', $form_state['values']['file_url_type']);
    $config->set('thumbnail.imglib', $form_state['values']['imglib']);
    $config->set('filesystem.fileperm', $form_state['values']['file_perm']);
    $config->set('filesystem.dirperm', $form_state['values']['dir_perm']);
    $config->set('misc.rememberlastdir', $form_state['values']['rememberlastdir']);
    $config->set('misc.usesystemjquery', $form_state['values']['usesystemjquery']);
    $config->set('thumbnail.tmbcrop', $form_state['values']['tmbcrop']);
    $config->set('filesystem.maxfilesize', $form_state['values']['max_filesize']);
    $config->set('filesystem.maxfilecount', $form_state['values']['max_filecount']);
    $config->set('filesystem.handleprivate', $form_state['values']['handleprivate']);
    $config->set('filesystem.public_root_label', $form_state['values']['filesystem_public_root_label']);
    $config->set('filesystem.private_root_label', $form_state['values']['filesystem_private_root_label']);
    $config->set('filesystem.unmanaged_root_label', $form_state['values']['filesystem_unmanaged_root_label']);
    $config->set('misc.manager_width', $form_state['values']['manager_width']);
    $config->set('misc.manager_height', $form_state['values']['manager_height']);
    $config->set('filesystem.allowed_extensions', $form_state['values']['filesystem_allowed_extensions']);

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
