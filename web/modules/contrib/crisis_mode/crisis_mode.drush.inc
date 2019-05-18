<?php

/**
 * @file
 * Contains the code to generate the custom drush commands.
 */

use Drupal\block\Entity\Block;

/**
 * Implements hook_drush_command().
 */
function crisis_mode_drush_command() {
  $items = [];
  $items['crisis-mode'] = [
    'description' => 'Enables the Crisis Mode Block.',
    'callback' => '_drush_crisis_mode_enable',
    'arguments' => [
      'on|off' => 'Flag to switch the crisis mode on or off. The default is \'on\'.',
    ],
    'drupal dependencies' => ['crisis_mode'],
  ];
  return $items;
}

/**
 * Callback for crisis-mode command.
 */
function _drush_crisis_mode_enable($flag = 'on') {
  $config = Drupal::service('config.factory')
    ->getEditable('crisis_mode.settings');
  if ($flag == 'on') {
    $config->set('crisis_mode_active', 1);
    $config->save();
    $block = Block::load('crisismodeblock');
    $block->enable();
    $block->save();
    drush_print(t('Crisis Mode enabled. Caches cleared ...'));
    Drupal::logger('Crisis Mode')->notice(t('Crisis Mode enabled'));
  }
  else {
    $config->set('crisis_mode_active', 0);
    $config->save();
    $block = Block::load('crisismodeblock');
    $block->disable();
    $block->save();
    drush_print(t('Crisis Mode disabled. Caches cleared ...'));
    Drupal::logger('Crisis Mode')->notice(t('Crisis Mode disabled'));
  }
}
