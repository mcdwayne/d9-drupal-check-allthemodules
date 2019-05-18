<?php

namespace Drupal\pagarme_marketplace\Helpers;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\pagarme\Helpers\PagarmeUtility;

class PagarmeMarketplaceUtility {
  public static function currencyAmountFormat($amount, $type = 'number') {
    $config = \Drupal::config('pagarme_marketplace.settings');
    $currency_code = $config->get('default_currency');

    if ($currency_code) {
      return PagarmeUtility::currencyAmountFormat($amount, $currency_code, $type);
    }
    return $amount;
  }

  public static function renderPager($current_path, $parameters) {
    $url = Url::fromUri('base:'.$current_path);
    $page = (!empty($parameters['page'])) ? $parameters['page'] : 1;
    $prev_markup = '';
    if ($page > 1) {
      $previous = $page - 1;
      $options = ['query' => ['page' => $previous]];
      $url->setOptions($options);
      $prev_markup = '<li class="pager__item">' . Link::fromTextAndUrl(t('Previous'), $url)->toString() . '</li>';
    }
    $next = $page + 1;
    $options = ['query' => ['page' => $next]];
    $url->setOptions($options);
    $next_markup = '<li class="pager__item">' . Link::fromTextAndUrl(t('Next'), $url)->toString() . '</li>';
    $markup = <<<MARKUP
      <nav class="pager" rprole="navigation aria-labeledby="pagination-heading">
        <h4 id="pagination-heading" class="visually-hidden">Pagination</h4>
        <ul class="pager__items js-pager__items">
          $prev_markup
          $next_markup
        </ul>
      </nav">
MARKUP;
    return $markup;
  }
}