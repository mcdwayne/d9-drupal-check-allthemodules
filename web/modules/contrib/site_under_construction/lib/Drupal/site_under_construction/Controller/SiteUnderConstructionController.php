<?php

/**
 * @file
 * Contains \Drupal\page_example\Controller\PageExampleController.
 */

namespace Drupal\site_under_construction\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 */
class SiteUnderConstructionController extends ConfigFormBase {

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager) {
    parent::__construct($config_factory);

    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory'), $container->get('path.alias_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'site_under_construction_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $site_config = $this->configFactory->get('system.site');
    $site_mail = $site_config->get('mail');
    if (empty($site_mail)) {
      $site_mail = ini_get('sendmail_from');
    }
    $options = $this->site_under_construction_tenplates();
    $form['site_under_construction_enable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable'),
      '#default_value' => \Drupal::state()->get('site_under_construction_enable', FALSE),
      '#description' => t('To perform this functionality mark this checked.')
    );
    $form['site_under_construction_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#description' => t('Insert browser title for template.'),
      '#default_value' => \Drupal::state()->get('site_under_construction_title', 'Home'),
    );
    $form['site_under_construction_favicon'] = array(
      '#type' => 'textfield',
      '#title' => t('Favicon'),
      '#description' => t('Insert browser favicon path.'),
      '#default_value' => \Drupal::state()->get('site_under_construction_favicon', 'core/misc/favicon.ico'),
    );
    $form['site_under_construction_templates_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Teplates'),
      '#description' => t('Insert the templates path where they are placed.'
          . 'Recommended libraries/site_under_construction_templates'),
      '#default_value' => \Drupal::state()->get('site_under_construction_templates_path', 'libraries/site_under_construction_templates'),
    );
    if (empty($options)) {
      $form['site_under_construction_markup'] = array(
        '#markup' => t('No templates directory exist under mentioned directory.'),
      );
    }
    else {
      $form['site_under_construction_templates'] = array(
        '#type' => 'radios',
        '#options' => $options,
        '#title' => t('Templates'),
        '#description' => t('Choose one template which you want to implement on '
            . 'your site.'),
        '#default_value' => \Drupal::state()->get('site_under_construction_templates', '')
      );
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // drupal_set_message("<pre>".print_r($form_state['values'], true)."</pre> ");
    \Drupal::state()->set('site_under_construction_enable', $form_state['values']['site_under_construction_enable']);
    \Drupal::state()->set('site_under_construction_title', $form_state['values']['site_under_construction_title']);
    \Drupal::state()->set('site_under_construction_favicon', $form_state['values']['site_under_construction_favicon']);
    \Drupal::state()->set('site_under_construction_templates_path', $form_state['values']['site_under_construction_templates_path']);
    if ($form_state['values']['site_under_construction_templates']) {
      \Drupal::state()->set('site_under_construction_templates', $form_state['values']['site_under_construction_templates']);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * 
   * @return array 
   *  An assocuative array contains template path as key and template html file
   * and directory as name.
   * 
   * @see buildForm
   */
  public function site_under_construction_tenplates() {
    $options = array();
    $dir = \Drupal::state()->get('site_under_construction_templates_path', 'libraries/site_under_construction_templates');
    $directories = $this->site_under_construction_scane($dir, '/.*\.(html|xhtml)$/', 2);  //file_scan_directory($dir, '/.*\.(html|xhtml)$/',  array('min_depth' => 2, 'recurse' => TRUE), 2);
    if (empty($directories)) {
      drupal_set_message(t('There is no templates in the mentioned directory.'));
    }

    foreach ($directories as $uri => $obj) {
      $split_dir = explode('/', $uri);
      $dir = $split_dir[count($split_dir) - 2];
      $options[$uri] = ucwords($dir) . ' ' . ucwords($obj->name);
    }
    return $options;
  }

  /**
   * 
   * @param type $dir
   *  The directory path where you have placed the templates.
   * @param type $mask
   *  Regular expression to which type of files you looking.
   * @param type $level
   *  At which level you want to search your desire files.
   * @param type $current_deph
   *  At which level function is looking files(inner use only).
   * 
   * 
   * @return Object
   *  Return the StdClass object that contains files paths and files name etc.
   * 
   * @see site_under_construction_tenplates.
   */
  public function site_under_construction_scane($dir, $mask, $level = 1, $current_deph = 1) {
    $files = array();
    if (is_dir($dir) && $handle = opendir($dir)) {

      while (FALSE !== ($filename = readdir($handle))) {
        if ($filename[0] != '.') {
          $uri = "$dir/$filename";
          $uri = file_stream_wrapper_uri_normalize($uri);

          if (is_dir($uri) && $current_deph < $level) {

            // Give priority to files in this folder by merging them in after any subdirectory files.
            $files = array_merge($this->site_under_construction_scane($uri, $mask, $level, $current_deph + 1), $files);
          }
          elseif (preg_match($mask, $filename)) {
            // Always use this match over anything already set in $files with the
            // same $$options['key'].
            $file = new \stdClass();
            $file->uri = $uri;
            $file->filename = $filename;
            $file->name = pathinfo($filename, PATHINFO_FILENAME);
            $files[$file->uri] = $file;
          }
        }
      }
      closedir($handle);
    }
    return $files;
  }

}
