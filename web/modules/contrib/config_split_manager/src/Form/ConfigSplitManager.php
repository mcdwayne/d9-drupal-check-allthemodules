<?php

namespace Drupal\config_split_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigSplitManager.
 *
 * @package Drupal\config_split_manager\Form
 */
class ConfigSplitManager extends ConfigFormBase {

  /**
   * Store the list of existing global modules.
   *
   */
  protected $existingGlobalModules;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'config_split_manager.ConfigSplitManager',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_split_manager';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // get enabled modules list
    $moduleHandler = \Drupal::moduleHandler();
    $modules = $moduleHandler->getModuleList();

    // set module categories to display
    $moduleCategories = array(
      'custom' => array(
        'path' => 'modules/custom/'
      ),
      'contrib' => array(
        'path' => 'modules/contrib/'
      ),
      'core' => array(
        'path' => 'core/modules/'
      ),
    );

    // define array for each module category
    foreach ($moduleCategories as $moduleCategoryKey => $moduleCategory) {
      ${$moduleCategoryKey . 'ModulesList'} = array();
    }

    // sort all modules based on categories
    foreach ($modules as $key => $module) {

      foreach ($moduleCategories as $moduleCategoryKey => $moduleCategory) {

        if(strpos($module->getPathname(), $moduleCategory['path']) !== false)
        {
          array_push(${$moduleCategoryKey . 'ModulesList'}, $key);
        }
      }
    }

    // load config split entities
    $config_split_entities = \Drupal::entityTypeManager()->getStorage('config_split')->loadMultiple();

    // ----- Table Header -----
    $header = array(
      array('data' => t('Module'), 'class' => ['checkbox'])
    );

    // add a header "All" for modules install globally
    array_push($header, array('data' => 'All', 'class' => ['checkbox']));

    // create header array
    foreach ($config_split_entities as $key => $config_split_entity) {

      if (is_object($config_split_entity) && !empty($key)) {
        array_push($header, array('data' => $key, 'class' => ['checkbox']));
      }
    }

    $form['modules'][]['#markup'] = t('This page let you control modules to add and remove from blacklist of already defined splits (environments).
    <br/>If you want to enable a module for some specific environment then uncheck "ALL" and select the checkbox below that environment. Required modules will automatically add into that enviroment if those are not part of global configuration.
    <br/>If you install any new module then that will automatically add into Dev split to avoid accidentally enable that module globally.');

    // now loop through each module category and enabled modules list
    foreach ($moduleCategories as $moduleCategoryKey => $moduleCategory) {

      // set the module category title
      $form['modules'][$moduleCategoryKey] = array(
        '#type' => 'details',
        '#title' => $this->t($moduleCategoryKey . ' Modules'),
        '#open' => TRUE,
      );

      // empty rows data for each module category
      $rows = array();

      $form['modules'][$moduleCategoryKey]['table'] = array(
        '#type' => 'table',
        '#header' => $header,
        '#id' => 'splits',
        '#attributes' => ['class' => ['splits', 'js-splits']],
        '#sticky' => TRUE,
      );

      // create rows of modules
      foreach (${$moduleCategoryKey . 'ModulesList'} as $row) {

        $isModuleGlobal = TRUE;

        $rows[$row] = array('data' => array(
          'module_name' => $row,
        ));

        $form['modules'][$moduleCategoryKey]['table'][$row]['module_name'] = [
          '#markup' => $row
        ];

        // create a "All" column checkbox
        $form['modules'][$moduleCategoryKey]['table'][$row]['all'] = [
          '#title' => $row . ': all' ,
          '#title_display' => 'invisible',
          '#wrapper_attributes' => [
            'class' => ['checkbox'],
          ],
          '#type' => 'checkbox',
          '#default_value' => 0,
          '#attributes' => ['class' => ['rid-all', 'js-rid-all']],
          '#parents' => ['all', $row],
        ];

        foreach ($config_split_entities as $key => $config_split_entity) {

          if (isset($modules_per_entity) && in_array($row, $modules_per_entity) && $isModuleGlobal) {
            $isModuleGlobal = FALSE;
          }

          if (is_object($config_split_entity) && !empty($key)) {

            $modules_per_entity = array_combine(array_keys($config_split_entities[$key]->get('module')), array_keys($config_split_entities[$key]->get('module')));

            $form['modules'][$moduleCategoryKey]['table'][$row][$key] = [
              '#title' => $row . ': ' . $key,
              '#title_display' => 'invisible',
              '#wrapper_attributes' => [
                'class' => ['checkbox'],
              ],
              '#type' => 'checkbox',
              '#default_value' => in_array($row, $modules_per_entity) ? 1 : 0,
              '#attributes' => ['class' => ['rid-' . $key, 'js-rid-' . $key]],
              '#parents' => [$key, $row],
            ];
          }
        }

        if ($isModuleGlobal) {
          // change the checkbox "All" selected
          $form['modules'][$moduleCategoryKey]['table'][$row]['all']['#default_value'] = $isModuleGlobal;

          $this->existingGlobalModules[] = $row;
        }

      }

    }

    $form['modules'][]['#markup'] = t('<em>Note: use drush command "<strong>drush csmex -y</strong>" to export configurations in your local environment and then committ to GIT repo.</em>');

    // add javascript to the page to control the checkbox "ALL" behavior
    $form['#attached']['library'][] = 'config_split_manager/drupal.config_split_manager.checkboxes';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // load config split entities
    $config_split_entities = \Drupal::entityTypeManager()->getStorage('config_split')->loadMultiple();

    $config_factory = \Drupal::configFactory();

    // get enabled modules list
    $moduleHandler = \Drupal::moduleHandler();
    // Sort all modules by their names.
    $allModules = system_rebuild_module_data();

    // extract all checked values of selected global (All) modules
    $globalModules = array_filter($form_state->getValue('all'));

    $modulesAddedInAll = array_diff_key($globalModules, array_flip($this->existingGlobalModules));

    // go through each split
    foreach ($config_split_entities as $environmentName => $config_split_entity) {

      if(is_object($config_split_entity)) {
        // extract all checked values
        $selectedModules = array_filter($form_state->getValue($environmentName));

        // change all checked values to 0
        $selectedModules = array_fill_keys(array_keys($selectedModules), 0);

        // get the array of newly selected modules list
        $arrayConfigSplitEntity = (Array)$config_split_entity;
        $prefix = chr(0).'*'.chr(0);
        $arryExistingModules = $arrayConfigSplitEntity[$prefix.'module'];
        $newlySelectedModulesList = array_diff_key($selectedModules, $arryExistingModules);

        // go through each newly added module to check if required modules are selected
        foreach ($newlySelectedModulesList as $key => $value) {
          if (!array_key_exists($key, $globalModules)) {

            $notEnabledModulesList = $this->areRequiredModulesEnabled($key, $allModules, $selectedModules, $globalModules);

            if ($notEnabledModulesList) {
              // display message for enabled modules
              drupal_set_message(t("Also enabled following required modules to enable <strong>@module</strong> module for <em>@enName</em>: <br/><em>@modulesList</em>", array('@module' => $key, '@enName' => $environmentName, '@modulesList' => implode(", ", $notEnabledModulesList))), 'status');
              $notEnabledModulesList = array_fill_keys($notEnabledModulesList, 0);
              // merge required modules to selectedModules array
              $selectedModules = array_merge($selectedModules, $notEnabledModulesList);
            }
          }
          else {
            unset($selectedModules[$key]);
            drupal_set_message(t("<strong>@module</strong> is enabled for all environments. If you want to enable for only specific environment then uncheck 'All' and then select for specific environment.<br/>Note: module will be uninstall from unchecked environment.", array('@module' => $key)));
          }
        }

        // now go through each new selected checkbox "ALL"
        // to remove those modules from split
        foreach ($modulesAddedInAll as $key => $value) {
          unset($selectedModules[$key]);
        }

        // now get the configurations of environment from DB
        $config = $config_factory->getEditable('config_split.config_split.' . $environmentName);
        // set and save updated configurations
        $config->set('module', $selectedModules);
        $config->save(TRUE);
      }
    }
  }

  /**
   * Call back function to return list of required modules those are not enabled
   */
  public function areRequiredModulesEnabled(string $moduleName, array $allModules, array $selectedModules, array $globalModules) {
    // get the list of required modules of selected module
    $requiredModulesList = $allModules[$moduleName]->requires;

    $notEnabledModulesList = array();

    foreach ($requiredModulesList as $key => $value) {
      if (!array_key_exists($key, $selectedModules) && !array_key_exists($key, $globalModules))
      {
        // add required modules in to array
        $notEnabledModulesList[] = $key;
      }
    }

    return $notEnabledModulesList;
  }

}
