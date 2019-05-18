<?php

namespace Drupal\page_hits\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'page_hits' block.
 *
 * @Block(
 *   id = "page_hits_block",
 *   admin_label = @Translation("Page Hits"),
 *   category = @Translation("Page Hits block")
 * )
 */
class PageHitsBlock extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  protected $configfactory;

  protected $account;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, AccountInterface $account, RequestStack $requestStack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configfactory = $config_factory;
    $this->account = $account;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configfactory->get('page_hits.settings');
    $ip = $this->requestStack->getCurrentRequest()->getClientIp();
    $unique_visitor = 0;
    $total_visitor = 0;
    $total_visitor_by_user = 0;
    $total_visitor_in_week = 0;
    $current_user = $this->account;

    global $base_url;
    $page_url = $base_url . $this->requestStack->getCurrentRequest()->getRequestUri();

    $result = [];
    $node = page_hits_page_type();
    if (!empty($node)) {
      $result = page_hits_get_data_by_nid($node->id());
    }
    else {
      $result = page_hits_get_data_by_url($page_url);
    }

    if (!empty($result)) {
      $ip = $result['ip'];
      $unique_visitor = $result['unique_visits'];
      $total_visitor = $result['total_visitors'];
      $total_visitor_by_user = $result['total_visitor_by_user'];
      $total_visitor_in_week = $result['total_visitor_in_week'];
    }

    $output = '<div id="counter">';
    $output .= '<ul>';

    if ($config->get('show_user_ip_address')) {
      $output .= '<li>' . $this->t('YOUR IP:') . '<strong>' . $ip . '</strong></li>';
    }
    if ($config->get('show_unique_page_visits')) {
      $output .= '<li>' . $this->t('UNIQUE VISITORS:') . '<strong>' . number_format($unique_visitor) . '</strong></li>';
    }
    if ($config->get('show_total_page_count')) {
      $output .= '<li>' . $this->t('TOTAL VISITORS:') . '<strong>' . number_format($total_visitor) . '</strong></li>';
    }
    if ($config->get('show_page_count_of_logged_in_user') &&  !empty($current_user) && !empty($current_user->id())) {
      $output .= '<li>' . $this->t('TOTAL VISITS BY YOU:') . '<strong>' . number_format($total_visitor_by_user) . '</strong></li>';
    }
    if ($config->get('show_total_page_count_of_week')) {
      $output .= '<li>' . $this->t('TOTAL VISITS IN THIS WEEK:') . '<strong>' . number_format($total_visitor_in_week) . '</strong></li>';
    }
    $output .= '</ul>';
    $build['#markup'] = $output;
    $build['#cache']['max-age'] = 0;
    $build['#allowed_tags'] = [
      'div', 'script', 'style', 'link', 'form',
      'h2', 'h1', 'h3', 'h4', 'h5',
      'table', 'thead', 'tr', 'td', 'tbody', 'tfoot',
      'img', 'a', 'span', 'option', 'select', 'input',
      'ul', 'li', 'br', 'p', 'link', 'hr', 'style', 'img',
    ];
    return $build;
  }

}
