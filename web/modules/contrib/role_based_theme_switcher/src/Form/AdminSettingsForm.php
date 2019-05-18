<?php

namespace Drupal\role_based_theme_switcher\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;
use Drupal\Core\Extension\ThemeHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Configure Role Based settings for this site.
 *
 * @category Role_Based_Theme_Switcher
 *
 * @package Role_Based_Theme_Switcher
 *
 * @link https://www.drupal.org/sandbox/pen/2760771 description
 */
class AdminSettingsForm extends ConfigFormBase {
  /**
   * Protected themeGlobal variable.
   *
   * @var themeGlobal
   */
  protected $themeGlobal;
  /**
   * Protected configFactory variable.
   *
   * @var configFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(ThemeHandler $themeGlobal, ConfigFactoryInterface $config_factory) {
    $this->themeGlobal = $themeGlobal;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('theme_handler'),
        $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'role_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'role_based_theme_switcher.RoleBasedThemeSwitchConfig',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Implements admin settings form.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load Themes List.
    $themes = $this->themeGlobal->listInfo();
    // Prepare array.
    $themeNames = ['' => '--Select--'];
    foreach ($themes as $key => $value) {
      $themeNames[$key] = $key;
    }
    // Load Roles.
    $roles = Role::loadMultiple();
    $roleThemes = $this->configFactory->get('role_based_theme_switcher.RoleBasedThemeSwitchConfig')->get('roletheme');
    $form['role_theme'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Themes List'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('There are no items yet. Add an item.', []),
      // TableDrag: Each array value is a list of callback arguments for
      // drupal_add_tabledrag().The #id of the table is automatically prepended;
      // if there is none, an HTML ID is auto-generated.
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'role_theme-order-weight',
        ],
      ],
    ];
    // Check if anything is assigned.
    // If any role is aasigned any theme.
    // Else Load list of themes on with all roles.
    if (!empty($roleThemes)) {
      // Check if any new role is added.
      // If yes then merge new roles also.
      if (count($roleThemes) == count($roles)) {
        $roles = $roleThemes;
      }
      elseif (count($roleThemes) != count($roles)) {
        foreach ($roles as $rem_key => $rem_val) {
          if (!array_key_exists($rem_key, $roleThemes)) {
            $merge[$rem_key] = ['id' => '', 'weight' => 10];
            $roleThemes = array_merge($roleThemes, $merge);
          }
        }
        $roles = $roleThemes;
      }
    }
    else {
      $roleThemes = [];
      $i = 0;
      foreach ($roles as $roles_key => $roles_val) {
        if ($roles_key == 'administrator') {
          $roleThemes[$roles_key]['weight'] = $i;
          $roleThemes[$roles_key]['id'] = 'seven';
          $i++;
        }
        else {
          $roleThemes[$roles_key]['weight'] = $i;
          $roleThemes[$roles_key]['id'] = 'bartik';
          $i++;
        }
      }
      $roles = $roleThemes;
    }
    // Build the table rows and columns.
    foreach ($roles as $id => $entity) {
      // TableDrag: Mark the table row as draggable.
      $form['role_theme'][$id]['#attributes']['class'][] = 'draggable';
      // TableDrag: Sort the table row according to its existing/configured
      // weight.
      // $form['role_theme'][$id]['#weight'] = (int) $roleThemes[$id]['weight'];
      // Some table columns containing raw markup.
      $form['role_theme'][$id]['label'] = [
        '#plain_text' => $this->t('@id User', ['@id' => ucfirst($id)]),
      ];

      $form['role_theme'][$id]['id'] = [
        '#type' => 'select',
        '#title' => $this->t('Select Theme'),
        '#options' => $themeNames,
        '#default_value' => (string) $roleThemes[$id]['id'],
      ];
      // TableDrag: Weight column element.
      $form['role_theme'][$id]['weight'] = [
        '#type' => 'weight',
        '#title_display' => 'invisible',
        '#default_value' => (int) $roleThemes[$id]['weight'],
        // Classify the weight element for #tabledrag.
        '#attributes' => ['class' => ['role_theme-order-weight']],
      ];
    }
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save changes'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $rollTheme = $form_state->getValue('role_theme');
    $role_arr = [];
    foreach ($rollTheme as $key => $value) {
      if (in_array((int) $value['weight'], $role_arr)) {
        $form_state->setErrorByName('role_theme', $this->t("There are errors in the form"));
      }
      $role_arr[$key] = (int) $value['weight'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $rollTheme = $form_state->getValue('role_theme');
    $role_arr = [];
    foreach ($rollTheme as $key => $value) {
      $role_arr[(int) $value['weight']] = ['theme' => $value['id'], 'role' => $key];

      $roles[] = $key;
    }

    ksort($role_arr);

    foreach ($role_arr as $new_key => $new_value) {

      if (in_array($new_value['role'], $roles)) {
        $roll_array[$new_value['role']] = ['id' => $new_value['theme'], 'weight' => $new_key];
      }
    }
    $roleThemes = $this->config('role_based_theme_switcher.RoleBasedThemeSwitchConfig');
    $roleThemes->set('roletheme', $roll_array);
    $roleThemes->save();
    drupal_set_message($this->t("Role theme configuration saved succefully"));
    // Clearing cache for anonymous users.
    drupal_flush_all_caches();
  }

}
