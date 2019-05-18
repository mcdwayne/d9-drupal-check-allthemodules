<?php

namespace Drupal\drupal_coverage_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupal_coverage_core\AnalysisManager;
use Drupal\drupal_coverage_core\BuildData;
use Drupal\drupal_coverage_core\Exception\UnableToDetermineBuildStatusException;
use Drupal\drupal_coverage_core\ModuleManager;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the analysis form.
 */
class AnalyseForm extends FormBase {

  /**
   * The AnalysisManager.
   *
   * @var AnalysisManager
   */
  protected $analysisManager;

  /**
   * The ModuleManager.
   *
   * @var ModuleManager
   */
  protected $moduleManager;

  /**
   * Indicated the current step in the form flow.
   *
   * @var int
   */
  protected $step = 1;

  /**
   * {@inheritdoc}
   */
  public function __construct(AnalysisManager $analysis_manager, ModuleManager $module_manager) {
    $this->analysisManager = $analysis_manager;
    $this->moduleManager = $module_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('drupal_coverage_core.analysis_manager'),
      $container->get('drupal_coverage_core.module_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drupal_coverage_core_analyse_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    switch ($this->step) {
      case 2:
        switch ($form_state->getValue(['type'])) {
          case ModuleManager::TYPE_CONTRIB:
            $this->buildContribForm($form, $form_state);
            break;

          case ModuleManager::TYPE_CORE:
            $this->buildCoreForm($form, $form_state);
            break;
        }
        break;

      case 1:
      default:
        $form['type'] = [
          '#type' => 'select',
          '#options' => [ModuleManager::TYPE_CONTRIB => ModuleManager::TYPE_CONTRIB, ModuleManager::TYPE_CORE => ModuleManager::TYPE_CORE],
          '#title' => $this->t('Type of project'),
        ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->step < 2 ? $this->t('Next') : $this->t('Analyse'),
    ];

    return $form;
  }

  /**
   * Function to build the core form.
   *
   * @param array $form
   *   The actual form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function buildCoreForm(array &$form, FormStateInterface $form_state) {
    $form['module'] = [
      '#type' => 'select',
      '#options' => $this->getCoreModules(),
      '#default_value' => $form_state->getValue('module'),
      '#title' => $this->t('Core module'),
    ];

    // @TODO Add to this selection when we can cater for Drupal 8.x
    $form['branch'] = [
      '#type' => 'select',
      '#options' => ['7.x' => '7.x'],
      '#title' => $this->t('Branch'),
    ];

    $form['type'] = [
      '#type' => 'hidden',
      '#value' => ModuleManager::TYPE_CORE,
    ];
  }

  /**
   * Function to build the contribution form.
   *
   * @param array $form
   *   The actual form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function buildContribForm(array &$form, FormStateInterface $form_state) {
    $form['module'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Module'),
      '#target_type' => 'node',
      '#default_value' => $form_state->getValue('module'),
      '#selection_settings' => ['target_bundles' => ['module']],
      '#required' => TRUE,
      '#selection_handler' => 'default',
      '#size' => 30,
      '#maxlength' => 1024,
    ];

    $form['branch'] = [
      '#type' => 'textfield',
      '#title' => t('Branch'),
      '#default_value' => $form_state->getValue('branch') ? $form_state->getValue('branch') : t('7.x-1.x'),
      '#required' => TRUE,
      '#description' => t('Specify the branch you want to analyse. This needs to be an existing branch inside the drupal.org repository.'),
    ];

    $form['type'] = [
      '#type' => 'hidden',
      '#value' => ModuleManager::TYPE_CONTRIB,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validatorForm(array &$form, FormStateInterface $form_state) {
    // @todo validate if branch exists.
    $this->analysisManager->isBeingBuild(
      $form_state->getValue('module'), $form_state->getValue('branch')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->step < 2) {
      $form_state->setRebuild();
      $this->step++;
    }
    else {

      switch ($form_state->getValue('type')) {
        case ModuleManager::TYPE_CORE:
          $this->submitCoreForm($form_state);
          break;

        case ModuleManager::TYPE_CONTRIB:
          $this->submitContribForm($form_state);
          break;
      }

    }

  }

  /**
   * Process the core analyse form.
   *
   * @param FormStateInterface $form_state
   *   The form state after submit.
   */
  public function submitCoreForm(FormStateInterface $form_state) {
    $build_data = new BuildData();
    $build_data->setModuleType(ModuleManager::TYPE_CORE);

    // @todo Group is not specified.
    $module = $this->moduleManager->getCoreModule($form_state->getValue('module'));
    $build_data->setModule($module);
    $build_data->setBranch($form_state->getValue('branch'));

    try {
      $this->analysisManager->startBuild($build_data);
      $url = $module->toUrl();
      $this->redirect($url->getRouteName(), $url->getRouteParameters(), $url->getOptions());
      drupal_set_message(t('The analysis for %module has been initiated.',
        ['%module' => $build_data->getModule()->title->getString()]));
    }
    catch (UnableToDetermineBuildStatusException $e) {
      drupal_set_message($this->t('Unable to determine the TravisCI build status. Please try again later.'), 'error');
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('An error occured.'), 'error');
    }
  }

  /**
   * Get the list of defined core modules.
   *
   * @return array
   *   Array of modules.
   */
  protected function getCoreModules() {
    $config = \Drupal::config('drupal_coverage_core.settings');
    $module_list = $config->get('modules')['drupal7'];
    $modules = [];

    foreach ($module_list as $machine_name => $module) {
      $modules[$machine_name] = $module['name'];
    }

    return $modules;
  }

  /**
   * Handles the contrib form submission.
   *
   * @param FormStateInterface $form_state
   *   The form state after submit.
   */
  public function submitContribForm(FormStateInterface $form_state) {
    $build_data = new BuildData();
    $build_data->setModuleType(ModuleManager::TYPE_CONTRIB);
    $build_data->setModule(Node::load($form_state->getValue('module')));
    $build_data->setBranch($form_state->getValue('branch'));

    try {
      if ($this->analysisManager->isBeingBuild($build_data->getModule(),
        $build_data->getBranch())
      ) {
        drupal_set_message(t('Currently there is already an analysis occuring for %module and %branch',
          [
            '%module' => $build_data->getModule()->title->getString(),
            '%branch' => $build_data->getBranch(),
          ]));
      }
      else {
        $this->analysisManager->startBuild($build_data);
        drupal_set_message(t('The analysis for %module has been initiated.',
          ['%module' => $build_data->getModule()->title->getString()]));
      }
    }
    catch (UnableToDetermineBuildStatusException $e) {
      drupal_set_message($this->t('Unable to determine the TravisCI build status. Please try again later.'), 'error');
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('An error occured.'), 'error');
    }
  }

}
