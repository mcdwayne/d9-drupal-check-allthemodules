<?php

namespace Drupal\mmenu\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Serialization\Yaml;

/**
 * Class MmenuSettingsForm.
 *
 * @package Drupal\mmenu\Form
 *
 * @ingroup mmenu
 */
class MmenuSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'mmenu_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $mmenu_name = '') {
    $mmenu = mmenu_list($mmenu_name);

    $form['general'] = array(
      '#tree' => TRUE,
      '#type' => 'details',
      '#title' => t('General'),
      '#weight' => -5,
      '#open' => TRUE,
    );

    $form['general']['enabled'] = array(
      '#title' => t('Enabled?'),
      '#type' => 'select',
      '#options' => array(
        1 => t('Yes'),
        0 => t('No'),
      ),
      '#default_value' => $mmenu['enabled'] ? 1 : 0,
      '#required' => TRUE,
      '#weight' => -3,
      '#description' => t('Enable or disable the mmenu.'),
    );

    $form['general']['name'] = array(
      '#type' => 'hidden',
      '#value' => $mmenu_name,
    );

    $block_options = mmenu_get_blocks();
//    dpm($block_options);
//    $block_options = array();
//    $block_options[] = t('--- Please select a block ---');
//    foreach ($drupal_blocks as $module => $drupal_block) {
//      foreach ($drupal_block as $id => $block) {
//        $block_options[$module . '|' . $block->getPluginId() . '|' . $id] = Unicode::ucfirst($module) . ' - ' . $block->label();
//      }
//    }

    $form['blocks'] = array(
      '#tree' => TRUE,
      '#type' => 'details',
      '#title' => t('Blocks'),
      '#weight' => 0,
      '#open' => TRUE,
    );

    $blocks = array();
    foreach ($mmenu['blocks'] as $k => $block) {
      $blocks[] = $block;
    }
    $allowed_blocks_nums = \Drupal::config('mmenu.settings')->get('allowed_blocks_nums');
    dpm($allowed_blocks_nums, '$allowed_blocks_nums');
    for ($i = count($blocks); $i < $allowed_blocks_nums; $i++) {
      $blocks[$i]['title'] = '';
      $blocks[$i]['plugin_id'] = '';
      $blocks[$i]['collapsed'] = TRUE;
      $blocks[$i]['wrap'] = FALSE;
    }

    foreach ($blocks as $k => $block) {
      $form['blocks'][$k] = array(
        '#tree' => TRUE,
        '#type' => 'details',
        '#title' => t('Block'),
        '#open' => !empty($block['plugin_id']),
      );
      $form['blocks'][$k]['plugin_id'] = array(
        '#title' => t('Select a block'),
        '#type' => 'select',
        '#options' => $block_options,
        '#default_value' => !empty($block['plugin_id']) ? $block['plugin_id'] : '',
        '#description' => t('Select a block to display on the mmenu.'),
      );
      $form['blocks'][$k]['menu_parameters'] = array(
        '#tree' => TRUE,
        '#type' => 'details',
        '#title' => t('Menu parameters'),
        '#open' => FALSE,
      );
      $options = array(1, 2, 3, 4, 5, 6, 7, 8, 9);
      $options = array_combine($options, $options);
      $form['blocks'][$k]['menu_parameters']['min_depth'] = array(
        '#title' => t('Min depth'),
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => (isset($block['menu_parameters']) && isset($block['menu_parameters']['min_depth'])) ? $block['menu_parameters']['min_depth'] : 1,
        '#description' => t('The minimum depth of menu links in the resulting tree. Defaults to 1, which is the default to build a whole tree for a menu (excluding menu container itself).'),
      );

      $form['blocks'][$k]['title'] = array(
        '#title' => t('Title'),
        '#type' => 'textfield',
        '#default_value' => $block['title'],
        '#description' => t('Override the default title for the block. Use <em>:placeholder</em> to display no title, or leave blank to use the default block title.', array(':placeholder' => '<none>')),
      );
      $form['blocks'][$k]['collapsed'] = array(
        '#title' => t('Collapsed'),
        '#type' => 'select',
        '#options' => array(
          1 => t('Yes'),
          0 => t('No'),
        ),
        '#default_value' => $block['collapsed'] ? 1 : 0,
        '#description' => t('Collapse or expand the block content by default.'),
      );
      $form['blocks'][$k]['wrap'] = array(
        '#title' => t('Wrap'),
        '#type' => 'select',
        '#options' => array(
          1 => t('Yes'),
          0 => t('No'),
        ),
        '#default_value' => $block['wrap'] ? 1 : 0,
        '#description' => t('Determine if needs to wrap the block content. Usually to set it to true if the block is not a system menu.'),
      );
    }

    $form['mmenu_options'] = array(
      '#tree' => TRUE,
      '#type' => 'details',
      '#title' => t('Mmenu options'),
      '#weight' => 1,
      '#open' => FALSE,
    );
    $form['mmenu_options']['yaml'] = array(
      '#title' => t('Enter YAML format settings'),
      '#type' => 'textarea',
      '#rows' => 20,
      '#required' => FALSE,
      '#default_value' => isset($mmenu['options']) ? Yaml::encode($mmenu['options']) : Yaml::encode(Json::encode(mmenu_get_default_options())),
      '#weight' => 0,
      '#description' => t('For more information about the options, please visit the page <a href=":link" target="_blank">:link</a>.', array(':link' => 'https://mmenu.frebsite.nl/documentation/core/options.html')),
    );

    $form['mmenu_configurations'] = array(
      '#tree' => TRUE,
      '#type' => 'details',
      '#title' => t('Mmenu configurations'),
      '#weight' => 2,
      '#open' => FALSE,
    );
    $form['mmenu_configurations']['yaml'] = array(
      '#title' => t('Enter YAML format settings'),
      '#type' => 'textarea',
      '#rows' => 20,
      '#required' => FALSE,
      '#default_value' => isset($mmenu['configurations']) ? Yaml::encode($mmenu['configurations']) : Yaml::encode(Json::encode(mmenu_get_default_configurations())),
      '#weight' => 0,
      '#description' => t('For more information about the configurations, please visit the page <a href=":link" target="_blank">:link</a>.', array(':link' => 'https://mmenu.frebsite.nl/documentation/core/configuration.html')),
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#weight' => 0,
    );
    $form['actions']['reset'] = array(
      '#type' => 'submit',
      '#value' => t('Reset'),
      '#weight' => 1,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    switch ($values['op']->__toString()) {
      case t('Save'):
//        $blocks = array();
//
//        // Updates the blocks.
//        foreach ($values['blocks'] as $k => $block) {
//          if (!empty($block['module_delta'])) {
//            list($module, , $id) = explode('|', $block['module_delta']);
//            $blocks[$k] = $block;
//            $blocks[$k] += array(
//              'module' => $module,
//              'delta' => $id,
//            );
//          }
//        }

        $mmenu = array(
          'enabled' => $values['general']['enabled'],
          'name' => $values['general']['name'],
          'blocks' => $values['blocks'],
          'options' => Yaml::decode($values['mmenu_options']['yaml']),
          'configurations' => Yaml::decode($values['mmenu_configurations']['yaml']),
        );

        $config = \Drupal::configFactory()->getEditable('mmenu.settings');
        $config->set('mmenu_item_' . $values['general']['name'], $mmenu);
        $config->save();

        // Clears mmenus cache.
        \Drupal::cache()->delete('mmenus:cache');

        drupal_set_message(t('The settings have been saved.'));
        break;

      case t('Reset'):
        // Deletes the mmenu settings from database.
        $config = \Drupal::configFactory()->getEditable('mmenu.settings');
        $config->delete('mmenu_item_' . $values['general']['name']);

        // Clears mmenus cache.
        \Drupal::cache()->delete('mmenus:cache');

        drupal_set_message(t('The settings have been reset.'));
        break;
    }
  }

}
