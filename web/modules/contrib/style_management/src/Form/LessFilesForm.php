<?php

namespace Drupal\style_management\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;

use Drupal\Core\State\StateInterface;
use Drupal\style_management\CompilerServiceInterface;
use Drupal\style_management\Controller\MainController;
use Zend\Feed\Uri;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LessFilesFrom.
 */
class LessFilesForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  protected $compilerService;

  /**
   * Define LessFilesForm constructor.
   *
   * @param object|\Drupal\Core\State\StateInterface $state
   *   The State interface.
   * @param object|\Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Config factory interface.
   * @param object|\Drupal\Core\Render\RendererInterface $renderer
   *   The render interface.
   * @param \Drupal\style_management\CompilerServiceInterface $compilerService
   *   The Compiler Service interface.
   */
  public function __construct(StateInterface $state, ConfigFactoryInterface $configFactory, RendererInterface $renderer, CompilerServiceInterface $compilerService) {
    $this->state = $state;
    $this->configFactory = $configFactory;
    $this->renderer = $renderer;
    $this->compilerService = $compilerService;
  }

  /**
   * Create container with all service.
   *
   * @param object|\Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Load Container.
   *
   * @return object|\Drupal\Core\Form\ConfigFormBase|\Drupal\style_management\Form\LessFilesForm
   *   Inject all service from container.
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
      'style_management.lessfiles',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'less_files_from';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get current config.
    $config_config = $this->state->get('style_management.config', '');

    // Get Lessfile config.
    $config = $this->config('style_management.lessfiles');

    // Check if processable_file exist.
    if (isset($config_config['processable_file']) && !empty($config_config['processable_file'])) {
      $processable_file = $config_config['processable_file'];
      if (count($processable_file) > 0) {

        // Check if LESS exist.
        if (isset($processable_file['less']) && !empty($processable_file['less'])) {
          $less = $processable_file['less'];
          if (count($less) > 0) {

            // Each options of single LESS file.
            foreach ($less as $key => $file_info) {

              $file_type = strtolower($key);
              $file_type = substr($file_type, -4);
              if ((!isset($form['groups'][$key]) && !empty($key)) && ($file_type == 'less')) {
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

                $aggregate = $file_info['aggregate'];
                if ($config->get('setting.aggregate--' . $file_id) !== NULL) {
                  $aggregate = $config->get('setting.aggregate--' . $file_id);
                }

                // Option - Aggregate.
                $form['groups'][$key]['aggregate--' . $file_id] = [
                  '#type' => 'checkbox',
                  '#title' => $this->t('Aggregate'),
                  '#default_value' => $aggregate,
                ];

                $destination_path = ($config->get('setting.destination_path--' . $file_id) !== NULL) ?
                  $config->get('setting.destination_path--' . $file_id) : $file_info['destination_path'];
                // Option - Destination Path.
                $form['groups'][$key]['destination_path--' . $file_id] = [
                  '#type' => 'textfield',
                  '#title' => $this->t('Destination Path'),
                  '#default_value' => $destination_path,
                ];

                $variables_from_file = $this->compilerService->getVariablesLessFromPath($key);
                $variables_from_config = $config->get('setting.alter_variables--' . $file_id, 'a:1:{s:0:"";s:0:"";}');
                $variables_from_config_arr = (((trim($variables_from_config) == 'N;') || $variables_from_config == '') || ($variables_from_config === NULL)) ? unserialize('a:1:{s:0:"";s:0:"";}') : unserialize($variables_from_config);

                $merged_varaibles = MainController::mergeVariables($variables_from_file, $variables_from_config_arr);

                $renderable = [
                  '#theme' => 'edit_variables',
                  '#items' => $merged_varaibles,
                ];
                $rendered = $this->renderer->render($renderable);

                $variables_from_config_json = json_encode($variables_from_config_arr);

                $form['groups'][$key]['alter_variables--' . $file_id] = [
                  '#type' => 'textarea',
                  '#title' => $this->t('Alter Variables'),
                  '#default_value' => $variables_from_config_json,
                  '#prefix' => '<div class="js-variables-editor-wrapper" file="' . $key . '">',
                  '#suffix' => $rendered . '</div>',
                ];
              }
            }
            $form['#attached']['library'][] = 'style_management/variables-editor';
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
        $this->configFactory->getEditable('style_management.lessfiles')
          ->set('setting.' . $key, $form_state->getValue($key))
          ->save();
      }

      // Aggregate.
      if (substr($key, 0, 11) == 'aggregate--') {

        $this->configFactory->getEditable('style_management.lessfiles')
          ->set('setting.' . $key, $form_state->getValue($key))
          ->save();
      }

      // Destination Path.
      if (substr($key, 0, 18) == 'destination_path--') {
        $this->configFactory->getEditable('style_management.lessfiles')
          ->set('setting.' . $key, $form_state->getValue($key))
          ->save();
      }

      // Override config.
      if (substr($key, 0, 17) == 'alter_variables--') {
        $data = json_decode($form_state->getValue($key), TRUE);
        $serialized_data = serialize($data);
        $this->configFactory->getEditable('style_management.lessfiles')
          ->set('setting.' . $key, $serialized_data)
          ->save();
      }
    }
  }

}
