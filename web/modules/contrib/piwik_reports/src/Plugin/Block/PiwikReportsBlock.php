<?php

namespace Drupal\piwik_reports\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\piwik_reports\PiwikData;

/**
 * Provides a 'PiwikReportsBlock' block.
 *
 * @Block(
 *  id = "piwik_page_report",
 *  admin_label = @Translation("Piwik page statistics"),
 * )
 */
class PiwikReportsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
          ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access piwik reports');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $renderer = \Drupal::service('renderer');
    $current_user = \Drupal::currentUser();
    $build = [];

    if (!\Drupal::moduleHandler()->moduleExists('matomo')) {
      $build['#markup'] = $this->t('To use this block, you need to install the <a href=":url">Matomo</a> module', array(':url' => 'https://www.drupal.org/project/matomo'));
      return $build;
    }

    // Build the data URL with all params.
    $token_auth = PiwikData::getToken();
    $piwik_url = PiwikData::getUrl();
    // message when no token?

    if(empty($piwik_url)) {
      $build['#markup'] = $this->t('Please configure the <a href=":url">Matomo settings</a> to use this block.', array(':url' => '/admin/config/system/matomo'));
      return $build;
    }

    $data_params = [];
    $data_params['idSite'] = \Drupal::config('matomo.settings')->get('site_id');
    $data_params['date'] = 'today';
    $data_params['period'] = 'year';
    $data_params['module'] = 'API';
    $data_params['method'] = 'Actions.getPageUrl';
    $data_params['pageUrl'] = urldecode($_SERVER['REQUEST_URI']);
    $data_params['format'] = 'JSON';
    if (!empty($token_auth)) {
      $data_params['token_auth'] = $token_auth;
    }
    $query_string = http_build_query($data_params);

    $build['#markup'] = '<div id="piwikpageviews"></div>';
    $build['#attached']['library'][] = 'piwik_reports/piwikreports';
    $build['#attached']['drupalSettings']['piwik_reports']['piwikJS']['url'] = $piwik_url;
    $build['#attached']['drupalSettings']['piwik_reports']['piwikJS']['query_string'] = $query_string;
    $build['#cache']['contexts'] = [
      'user',
      'url',
    ];
    $renderer->addCacheableDependency($build, \Drupal\user\Entity\User::load($current_user->id()));

    return $build;
  }
}
