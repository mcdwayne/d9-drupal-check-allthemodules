<?php

namespace Drupal\vertical_tabs_config\Form;

use Drupal\node\Entity\NodeType;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure visibility for this site.
 */
class VisibilityConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vt_visibility_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'vertical_tabs_config.visibility',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $roles = user_roles();
    $ct_list = NodeType::loadMultiple();
    $vertical_tabs = vertical_tabs_config_vertical_tab_list();

    $conf = vertical_tabs_config_get_config();

    $form['desc'] = [
      '#type' => 'item',
      '#markup' => $this->t('For each content type, select which vertical tabs need to be hidden depending on roles.'),
    ];

    $form['content_types_config'] = [
      '#type' => 'vertical_tabs',
    ];

    foreach ($ct_list as $ct_machine_name => $obj) {

      $form['hide_' . $ct_machine_name] = [
        '#weight' => 5,
        '#type' => 'details',
        '#title' => $obj->get('name'),
        '#group' => 'content_types_config',
      ];

      $form['hide_' . $ct_machine_name]['config'] = [
        '#type' => 'fieldset',
        '#collapsible' => FALSE,
        '#collapsed' => TRUE,
      ];

      $form['hide_' . $ct_machine_name]['config']['desc'] = [
        '#type' => 'item',
        '#markup' => $this->t("Select all vertical tabs that will be hidden for @ct. If you don't select any role, vertical tabs will be hidden to all roles.", ['@ct' => $obj->get('name')]),
      ];

      $form['hide_' . $ct_machine_name]['config']['roles'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Hide only by role'),
        '#weight' => 5,
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
        '#group' => 'content_types_config_roles',
      ];

      $form['hide_' . $ct_machine_name]['config']['tabs'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Hidded vertical tabs'),
        '#weight' => 6,
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
        '#group' => 'content_types_config_tabs',
      ];

      foreach ($roles as $rid => $value) {

        $def = 0;
        if (isset($conf[$ct_machine_name]['roles']) && is_array($conf[$ct_machine_name]['roles'])) {
          if (in_array($rid, $conf[$ct_machine_name]['roles'])) {
            $def = 1;
          }
        }

        $form['hide_' . $ct_machine_name]['config']['roles']['role_' . $ct_machine_name . '_' . $rid] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Apply config for') . ' ' . $value->get('label'),
          '#default_value' => $def,
          '#group' => 'vertical_tabs_roles',
        ];
      }

      foreach ($vertical_tabs as $vt_machine_name => $vt_human_name) {

        $def = isset($conf[$ct_machine_name][$vt_machine_name]) ? $conf[$ct_machine_name][$vt_machine_name] : 0;

        $form['hide_' . $ct_machine_name]['config']['tabs']['hide_' . $ct_machine_name . '_' . $vt_machine_name] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Hide') . ' ' . $vt_human_name,
          '#default_value' => $def,
          '#group' => 'vertical_tabs_hide',
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $config = [];

    $roles = user_roles();
    $ct_list = NodeType::loadMultiple();
    $vertical_tabs = vertical_tabs_config_vertical_tab_list();

    foreach ($ct_list as $ct_machine_name => $obj) {

      $selected_roles = [];
      foreach ($roles as $rid => $value) {
        if ($values['role_' . $ct_machine_name . '_' . $rid] == 1) {
          $selected_roles[] = $rid;
        }
      }

      foreach ($vertical_tabs as $vt_machine_name => $vt_human_name) {

        $data = [
          'vertical_tab' => $vt_machine_name,
          'content_type' => $ct_machine_name,
          'hidden' => $values['hide_' . $ct_machine_name . '_' . $vt_machine_name],
          'roles' => json_encode($selected_roles),
        ];
        $config[] = $data;
      }
    }

    $this->verticalTabsConfigSaveConfig($config);

    parent::submitForm($form, $form_state);
  }

  /**
   * Save all configuration.
   *
   * @param array $config
   *   The array ready to save to database.
   */
  public function verticalTabsConfigSaveConfig(array $config) {
    $query = Database::getConnection()->insert('vertical_tabs_config')->fields(['vertical_tab',
      'content_type',
      'roles',
      'hidden',
      ]
    );

    foreach ($config as $record) {
      $query->values($record);
    }

    try {
      Database::getConnection()->delete('vertical_tabs_config')->execute();
      $query->execute();
    }
    catch (Exception $e) {
      \Drupal::logger('vertical_tabs_config')->notice($e->getMessage());
    }
  }

}
