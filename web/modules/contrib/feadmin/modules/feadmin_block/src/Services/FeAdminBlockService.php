<?php
/**
 * @file
 * Provides an API for managing blocks from front.
 *
 * Sponsored by: www.freelance-drupal.com
 */

namespace Drupal\feadmin_block\Services;

use Drupal\block\Entity\Block;
use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Helper methods for feadmin_block
 */
class FeAdminBlockService {
  use StringTranslationTrait;

  /**
   * Gets information about the impact of editing a given block
   *
   * @param $block_id
   *   The given block ID.
   *
   * @return array
   *   A render array of information regarding the block edition impacts.
   */
  public function getImpactInformations($block_id) {
    $list = array();

    $visibility = Block::load($block_id)->getVisibility();

    // Check for page visibility.
    $this->getLanguagesVisibility($list, $visibility['language']);

    // Check for page visibility.
    $this->getNodeTypesVisibility($list, $visibility['node_type']);

    // Check for page visibility.
    $this->getPagesVisibility($list, $visibility['request_path']);

    // Check for user roles visibility.
    $this->getUserRolesVisibility($list, $visibility['user_role']);

    // All pages affected if the impact list is empty.
    if (empty($list)) {
      $list['all'] = array(
        '#markup' => $this->t('Every single pages of your site.'),
      );
    }

    return array(
      '#theme' => 'item_list',
      '#items' => $list,
      '#attributes' => array(
        'class' => array('feadmin-warnings'),
      )
    );
  }

  /**
   * Analysis language visibility settings.
   *
   * @param $list
   *   The render array in which to add impact warnings.
   * @param $config
   *   The language visibility configuration.
   * @return string
   *   A string for language visibility details.
   */
  private function getLanguagesVisibility(&$list, $config) {
    if (!empty($config)) {

      // Build message.
      $message = $this->t('Every pages for the following language(s):') . ' ';
      $message .= Html::escape(implode(', ', $config['langcodes'])) . '.';

      // Return language visibility.
      $list['language'] = array(
        '#markup' => $message
      );
    }
  }

  /**
   * Analysis node types visibility settings.
   *
   * @param $list
   *   The render array in which to add impact warnings.
   * @param $config
   *   The node types visibility configuration.
   * @return string
   *   A string for node types visibility details.
   */
  private function getNodeTypesVisibility(&$list, $config) {
    if (!empty($config)) {

      // Build message.
      $message = $this->t('Every pages of the following type(s):') . ' ';
      $message .= Html::escape(implode(', ', $config['bundles'])) . '.';

      // Return node types visibility.
      $list['node_type'] = array(
        '#markup' => $message
      );
    }
  }

  /**
   * Analysis page visibility settings.
   *
   * @param $list
   *   The render array in which to add impact warnings.
   * @param $config
   *   The page visibility configuration.
   * @return string
   *   A string for page visibility details.
   */
  private function getPagesVisibility(&$list, $config) {
    if (!empty($config)) {
      // List node types.
      $pages = array_map('trim', explode("\n", $config['pages']));

      // Build message.
      if ($config['negate']) {
        $message = $this->t('Every pages excepted the following:') . ' ';
      }
      else {
        $message = $this->t('Each of these pages:') . ' ';
      }
      $message .= Html::escape(implode(', ', $pages)) . '.';

      // Return node types visibility.
      $list['pages'] = array(
        '#markup' => $message
      );
    }
  }

  /**
   * Analysis user role visibility settings.
   *
   * @param $list
   *   The render array in which to add impact warnings.
   * @param $config
   *   The user role visibility configuration.
   * @return string
   *   A string for user role visibility details.
   */
  private function getUserRolesVisibility(&$list, $config) {
    if (!empty($config)) {

      // Build message.
      if ($config['negate']) {
        $message = $this->t('All users but those of the following role(s):') . ' ';
      }
      else {
        $message = $this->t('Every users of the following role(s):') . ' ';
      }
      $message .= Html::escape(implode(', ', $config['roles'])) . '.';

      // Return node types visibility.
      $list['user_role'] = array(
        '#markup' => $message
      );
    }
  }
}