<?php

namespace Drupal\doubleclick_floodlight\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'DoubleclickBlock' block.
 * @Block(
 *  id = "doubleclick_block",
 *  admin_label = @Translation("Doubleclick block"),
 * )
 */
class DoubleclickBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get info from admin form settings.
    $config = \Drupal::config('doubleclick_floodlight.DoubleclickSettings');

    $enabled = $config->get('doubleclick_floodlight_enabled');
    $region = $config->get('doubleclick_floodlight_region');
    $account_id = $config->get('doubleclick_floodlight_account_id');
    $type = $config->get('doubleclick_floodlight_type');
    $cat = $config->get('doubleclick_floodlight_cat');
    $show_standard = $config->get('doubleclick_floodlight_show_standard');
    $show_unique = $config->get('doubleclick_floodlight_show_unique');


    return array(
      '#theme' => 'doubleclick_floodlight_block',
      '#account_id' => $account_id,
      '#type' => $type,
      '#cat' => $cat,
      '#show_standard' => $show_standard,
      '#show_unique' => $show_unique,
      '#enabled' => $enabled,
      '#region' => $region,
    );


  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'account_id' => '',
      'type' => '',
      'cat' => '',
      'show_standard' => '',
      'show_unique' => '',
      'enabled' => '',
      'region' => '',
    ];
  }



  public function create() {

    $config = \Drupal::config('doubleclick_floodlight.DoubleclickSettings');

    $region = $config->get('doubleclick_floodlight_region');

    $default_theme = \Drupal::config('system.theme')->get('default');


    return array(
      // A unique ID for the block instance.
      'id' => 'doubleclick_settings_form',
      // The plugin block id as defined in the class.
      'plugin' => 'doubleclick_settings_form',
      // The machine name of the theme region.
      'region' => $region,
      'settings' => array(
        'label' => 'Execute PHP',
      ),
      // The machine name of the theme.
      'theme' => $default_theme,
      'visibility' => array(),
      'weight' => 100,
    );
    $block = \Drupal\block\Entity\Block::create($values);
    $block->save();
  }


  protected function placeBlock($plugin_id, array $settings = array()) {
    $config = \Drupal::configFactory();
    $block_config = \Drupal::config('doubleclick_floodlight.DoubleclickSettings');
    $region = $block_config->get('doubleclick_floodlight_region');
    $settings += array(
      'plugin' => 'doubleclick_settings_form',
      'region' => $region,
      'id' => strtolower($this->randomMachineName(8)),
      'theme' => $config->get('system.theme')->get('default'),
      'label' => $this->randomMachineName(8).'TEST',
      'visibility' => array(),
      'weight' => 0,
    );
    $values = [];
    foreach (array('region', 'id', 'theme', 'plugin', 'weight', 'visibility') as $key) {
      $values[$key] = $settings[$key];
      // Remove extra values that do not belong in the settings array.
      unset($settings[$key]);
    }
    foreach ($values['visibility'] as $id => $visibility) {
      $values['visibility'][$id]['id'] = $id;
    }
    $values['settings'] = $settings;
    $block = Block::create($values);
    $block->save();
    return $block;
  }
}
