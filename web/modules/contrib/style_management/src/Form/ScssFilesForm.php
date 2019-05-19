<?php

namespace Drupal\style_management\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\style_management\Controller\MainController;
use Zend\Feed\Uri;

/**
 * Class ScssFilesForm.
 */
class ScssFilesForm extends ConfigFormBase {

  /**
   * Load State in Class.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * ScssFilesForm constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   Load Stete Interface from service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * Load info from Container.
   *
   * @param object|\Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('config.factory'),
      $container->get('renderer'),
      $container->get('style_management.compiler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'style_management.scssfiles',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scss_files_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get current config.
    $config_config = $this->state->get('style_management.config', '');
    // Get Scssfile config.
    $config = $this->config('style_management.scssfiles');

    if (isset($config_config['processable_file']) && !empty($config_config['processable_file'])) {
      $processable_file = $config_config['processable_file'];
      if (count($processable_file) > 0) {
        if (isset($processable_file['scss']) && !empty($processable_file['scss'])) {
          $scss = $processable_file['scss'];
          if (count($scss) > 0) {

            // Each options of single SCSS file.
            foreach ($scss as $key => $file_info) {

              if (!isset($form['groups'][$key]) && !empty($key)) {
                $file_id = MainController::makeFileId($key);

                // Details Wrapper.
                $form['groups'][$key] = [
                  '#type' => 'details',
                  '#title' => $key,
                ];

                $watch = $file_info['watch'];
                if ($config->get('setting.watch--' . $file_id) !== NULL) {
                  $watch = $config->get('setting.watch--' . $file_id);
                }

                // Option - Watch.
                if (isset($file_info['watch'])) {
                  $form['groups'][$key]['watch--' . $file_id] = [
                    '#type' => 'checkbox',
                    '#title' => $this->t('Watcher'),
                    '#default_value' => $watch,
                  ];
                  // Open groups if is watched.
                  $form['groups'][$key]['#open'] = $watch;
                }

                $destination_path = ($config->get('setting.destination_path--' . $file_id) !== NULL) ?
                  $config->get('setting.destination_path--' . $file_id) : $file_info['destination_path'];
                // Option - Destination Path.
                $form['groups'][$key]['destination_path--' . $file_id] = [
                  '#type' => 'textfield',
                  '#title' => $this->t('Destination Path'),
                  '#default_value' => $destination_path,
                ];

              }
            }
          }
        }
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validate folder.
    foreach ($form_state->getValues() as $key => $value) {
      if (substr($key, 0, 18) == 'destination_path--') {
        $destination_path = $form_state->getValue($key);
        if (Uri::factory($destination_path)->isValid() || ($destination_path === '')) {
          if (!file_prepare_directory($destination_path, FILE_CREATE_DIRECTORY) && $destination_path != '') {
            $form_state->setErrorByName($key, $this->t('Check permission on this path: <b>%path</b>', ['%path' => $destination_path]));
          }
        }
        else {
          // @TODO make error massage, not valid path.
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    foreach ($form_state->getValues() as $key => $value) {

      // Watch.
      if (substr($key, 0, 7) == 'watch--') {
        $this->configFactory->getEditable('style_management.scssfiles')
          ->set('setting.' . $key, $form_state->getValue($key))
          ->save();
      }

      // Destination Path.
      if (substr($key, 0, 18) == 'destination_path--') {
        $this->configFactory->getEditable('style_management.scssfiles')
          ->set('setting.' . $key, $form_state->getValue($key))
          ->save();
      }
    }
  }

}
