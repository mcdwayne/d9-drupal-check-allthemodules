<?php

namespace Drupal\mmu\Form;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Form\ModulesListForm as SystemModuleListForm;

/**
 * {@inheritdoc}
 */
class ModulesListForm extends SystemModuleListForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    require_once DRUPAL_ROOT . '/core/includes/install.inc';
    $distribution = drupal_install_profile_distribution_name();

    // Include system.admin.inc so we can use the sort callbacks.
    $this->moduleHandler->loadInclude('system', 'inc', 'system.admin');

    // Sort all modules by their names.
    $modules = system_rebuild_module_data();
    uasort($modules, 'system_sort_modules_by_info_name');

    $packages = [];

    // Iterate over each of the modules.
    $form['modules']['#tree'] = TRUE;
    foreach ($modules as $machine_name => $module) {
      if (empty($module->info['hidden'])) {
        $form['modules'][$machine_name] = $this->buildRow($modules, $module, $distribution);
        $form['modules'][$machine_name]['#theme'] = 'mmu_modules_item';
        $form['modules'][$machine_name]['#module'] = $module;
        $form['modules'][$machine_name]['#machine_name'] = $machine_name;

        $package = $module->info['package'];
        $packages[HTML::getClass($package)] = $package;
      }
    }
    ksort($packages);

    $packages = ['any' => $this->t('- ANY -')] + $packages;

    $defaults = [];
    if (isset($_COOKIE['mmu-active-filter'])) {
      $filter_defaults = explode('.mmu-', (string) new HtmlEscapedText($_COOKIE['mmu-active-filter']));
      array_shift($filter_defaults);
      foreach ($filter_defaults as $filter_default) {
        list($key, $value) = array_pad(explode('-', $filter_default, 2), 2, NULL);
        $defaults[$key] = $value;
      }
    }

    if (isset($_COOKIE['mmu-active-sort'])) {
      list($defaults['sort_by'], $defaults['sort_order'])
        = array_pad(explode(':', new HtmlEscapedText($_COOKIE['mmu-active-sort'])), 2, NULL);
    }

    $form['controls'] = [
      '#title' => 'controls',
      '#type' => 'container',
      '#attributes' => ['class' => ['mmu-controls', 'clearfix']],
      '#weight' => -100,
    ];

    $form['controls']['text'] = [
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#size' => 30,
      '#placeholder' => $this->t('Enter module name'),
      '#attributes' => [
        'class' => ['table-filter-text'],
        'data-table' => '#system-modules',
        'autocomplete' => 'off',
      ],
    ];

    $form['controls']['package'] = [
      '#title' => $this->t('Package'),
      '#type' => 'select',
      '#options' => $packages,
      '#default_value' => isset($defaults['package']) ? $defaults['package'] : '',
    ];

    $form['controls']['status'] = [
      '#title' => $this->t('Status'),
      '#type' => 'select',
      '#options' => [
        'any' => $this->t('- ANY -'),
        'selected' => $this->t('Selected'),
        'enabled' => $this->t('Enabled'),
        'disabled' => $this->t('Disabled'),
      ],
      '#default_value' => isset($defaults['status']) ? $defaults['status'] : '',
    ];

    $form['controls']['source'] = [
      '#title' => $this->t('Source'),
      '#type' => 'select',
      '#options' => [
        'any' => $this->t('- ANY -'),
        'core' => $this->t('Core'),
        'contrib' => $this->t('Contrib'),
        'custom' => $this->t('Custom'),
      ],
      '#default_value' => isset($defaults['source']) ? $defaults['source'] : '',
    ];

    $form['controls']['sort_by'] = [
      '#title' => $this->t('Sort by'),
      '#type' => 'select',
      '#options' => [
        'package' => $this->t('Package'),
        'name' => $this->t('Name'),
        'status' => $this->t('Status'),
      ],
      '#default_value' => isset($defaults['sort_by']) ? $defaults['sort_by'] : '',
    ];

    $form['controls']['sort_order'] = [
      '#title' => $this->t('Order'),
      '#type' => 'select',
      '#options' => [
        'asc' => $this->t('Asc'),
        'desc' => $this->t('Desc'),
      ],
      '#default_value' => isset($defaults['sort_order']) ? $defaults['sort_order'] : '',
    ];

    $form['controls']['reset'] = [
      '#title' => $this->t('Order'),
      '#type' => 'button',
      '#value' => $this->t('Reset'),
      '#attributes' => ['class' => ['mmu-reset-button']],
      '#prefix' => '<div class="form-item"><div>&nbsp;</div>',
      '#suffix' => '</div>',
    ];

    $form['modules']['#prefix'] = '<div id="mmu-container" class="clearfix">';
    $form['modules']['#suffix'] = '</div>';

    $form['controls']['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['form-item']],
    ];
    $form['controls']['actions']['total_selected'] = [
      '#type' => 'container',
      '#markup' => '&nbsp;',
    ];

    $form['controls']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
    ];

    $form['summary'] = [
      '#type' => 'item',
      '#markup' => '',
    ];

    $form['#attached']['library'][] = 'mmu/mmu';
    $form['#attached']['library'][] = 'core/jquery.cookie';
    $form['#attached']['library'][] = 'core/drupal.debounce';
    $form['#attached']['library'][] = 'core/jquery.ui.dialog';
    $form['#attached']['library'][] = 'core/jquery.ui.effects.explode';

    $form['#attributes']['class'][] = 'mmu-modules-list';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildModuleList(FormStateInterface $form_state) {
    $submitted_modules = $form_state->getValue('modules');

    // Build a list of modules to install.
    $modules = [
      'install' => [],
      'dependencies' => [],
    ];

    // Required modules have to be installed.
    // @todo This should really not be handled here.
    $data = system_rebuild_module_data();
    foreach ($data as $name => $module) {
      if (!empty($module->required) && !$this->moduleHandler->moduleExists($name)) {
        $modules['install'][$name] = $module->info['name'];
      }
    }

    // First, build a list of all modules that were selected.
    foreach ($submitted_modules as $name => $checkbox) {
      if ($checkbox['enable'] && !$this->moduleHandler->moduleExists($name)) {
        $modules['install'][$name] = $data[$name]->info['name'];
      }
    }

    // Add all dependencies to a list.
    while (list($module) = each($modules['install'])) {
      foreach (array_keys($data[$module]->requires) as $dependency) {
        if (!isset($modules['install'][$dependency]) && !$this->moduleHandler->moduleExists($dependency)) {
          $modules['dependencies'][$module][$dependency] = $data[$dependency]->info['name'];
          $modules['install'][$dependency] = $data[$dependency]->info['name'];
        }
      }
    }

    // Make sure the install API is available.
    include_once DRUPAL_ROOT . '/core/includes/install.inc';

    // Invoke hook_requirements('install'). If failures are detected, make
    // sure the dependent modules aren't installed either.
    foreach (array_keys($modules['install']) as $module) {
      if (!drupal_check_module($module)) {
        unset($modules['install'][$module]);
        foreach (array_keys($data[$module]->required_by) as $dependent) {
          unset($modules['install'][$dependent]);
          unset($modules['dependencies'][$dependent]);
        }
      }
    }

    return $modules;
  }

}
