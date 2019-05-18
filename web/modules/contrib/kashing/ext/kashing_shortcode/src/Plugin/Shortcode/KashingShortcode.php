<?php

namespace Drupal\kashing_shortcode\Plugin\Shortcode;

use Drupal\user\Entity\User;
use Drupal\Core\Language\Language;
use Drupal\Core\Url;
use Drupal\kashing\Entity\KashingValid;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * Provides a shortcode for Kashing.
 *
 * @Shortcode(
 *   id = "kashing",
 *   title = @Translation("Kashing"),
 *   description = @Translation("Add Kashing payment method")
 * )
 */
class KashingShortcode extends ShortcodeBase {

  /**
   * Kashing shortcode render function.
   *
   * @param $attributes
   *   Attributes
   * @param string $text
   *   Text.
   * @param string $langcode
   *   Langcode
   *   Langcode.
   *
   * @return string
   *   Render ready HTML
   */
  public function process($attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {

    $base_url = Url::fromUri('internal:/')->setAbsolute()->toString();

    $config = \Drupal::service('config.factory')->getEditable('kashing.settings');
    $config_errors = FALSE;
    $page_info = '<div class="kashing-frontend-notice kashing-errors">';
    $error_info = '';

    $kashing_validate = new KashingValid();

    $mode = $config->get('mode');

    if ($mode == 'test') {
      $merchant_id = $config->get('key.test.merchant');
      if (!$kashing_validate->validateRequiredField($merchant_id)) {
        $config_errors = TRUE;
        $error_info .= '<li>' . t('No test merchant ID provided.') . '</li>';
      }

      $secret_key = $config->get('key.test.secret');
      if (!$kashing_validate->validateRequiredField($secret_key)) {
        $config_errors = TRUE;
        $error_info .= '<li>' . t('No test secret key provided.') . '</li>';
      }
    }
    elseif ($mode == 'live') {
      $merchant_id = $config->get('key.live.merchant');
      if (!$kashing_validate->validateRequiredField($merchant_id)) {
        $config_errors = TRUE;
        $error_info .= '<li>' . t('No live merchant ID provided.') . '</li>';
      }

      $secret_key = $config->get('key.live.secret');
      if (!$kashing_validate->validateRequiredField($secret_key)) {
        $config_errors = TRUE;
        $error_info .= '<li>' . t('No live secret key provided.') . '</li>';
      }
    }
    else {
      $config_errors = TRUE;
      $error_info .= '<li>' . t('No Kashing mode selected.') . '</li>';
    }

    // No configuration errors.
    if (!$config_errors) {
      $attributes = $this->getAttributes(['id' => ''], $attributes);
      $block_id = $attributes['id'];

      // Load block entity and render it.
      if ($block = entity_load('block', $block_id)) {

        $view = entity_view($block, 'block');

        $render = drupal_render($view);

      }
      // Block with given id doesnt not exist.
      else {
        // Check if user is administrator.
        $user_id = \Drupal::currentUser()->id();
        $is_admin = User::load($user_id)->hasRole('administrator');

        if ($is_admin) {

          $page_info .= '<p><strong>' . t('Kashing Payments plugin configuration errors:') . ' </strong></p><ul>';
          $page_info .= '<li> Wrong block ID </li>';
          $page_info .= '</ul><a href="' . $base_url . '/admin/config/kashing" target="_blank">' . t('Visit the module settings') . '</a>';

        }
        else {
          $page_info = '<p>' . t('Something went wrong. Please contact the site administrator.') . '</p>';
        }

        $page_info .= '</div>';

        $render = $page_info;
      }
    }
    // There are some configuration errors.
    else {
      // Check if user is administrator.
      $user_id = \Drupal::currentUser()->id();
      $is_admin = User::load($user_id)->hasRole('administrator');

      if ($is_admin) {

        $page_info .= '<p><strong>' . t('Kashing Payments plugin configuration errors:') . ' </strong></p><ul>';
        $page_info .= $error_info;
        $page_info .= '</ul><a href="' . $base_url . '/admin/config/kashing" target="_blank">' . t('Visit the plugin settings') . '</a>';

      }
      else {
        $page_info = '<p>' . t('Something went wrong. Please contact the site administrator.') . '</p>';
      }

      $page_info .= '</div>';
      $render = $page_info;
    }

    return $render;
  }

  /**
   * Kashing shortcode info page.
   *
   * @param bool $long
   *   Long.
   *
   * @return string
   *   Tips for using shotcode
   */
  public function tips($long = FALSE) {

    $output = [];
    $output[] = '<strong>' . t('[kashing id=[ID] /]') . '</strong> ';

    if ($long) {
      $output[] = t('Insert Kashing payment method.');
    }
    else {
      $output[] = t('Insert Kashing payment method.');
    }

    return implode(' ', $output);

  }

}
