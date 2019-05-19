<?php
/**
 * @file
 * Help page fragments for Visual Website Optimizer module.
 */

use Drupal\Core\Url;

/**
 * Help page for both the generic help for the module, and settings page.
 */
function _visual_website_optimizer_help_settings() {

  list($vwo_url_partner, $vwo_url_nopartner) = _visual_website_optimizer_help_links();
  $vwo_settings = Url::fromRoute('visual_website_optimizer.settings');

  $h[] = t('Visual Website Optimizer is an easy to use A/B, split and multivariate testing tool.');
  $h[] = t('In order to use this module, you will need a Visual Website Optimizer account. A Free 30 day trial account is available from the VWO webite by clicking on either of the links below. To contribute towards the continued development of this module for free and with no effort, click on the affilitate link. Otherwise, click the other link without the affiliate marker.');
  $h2[] = t(
    '<a href=":url">Sign up for a Free 30 day trial account AND help out</a>',
    array(':url' => $vwo_url_partner->toString())
  );
  $h2[] = ', ' . t('or') . ' ';
  $h2[] = t(
    '<a href=":url">Sign up for a Free 30 day trial without affiliate link.</a>',
    array(':url' => $vwo_url_nopartner->toString())
  );
  $h[] = implode('', $h2);
  $h[] = t(
    'Once the Account ID has been set on the <a href=":url">settings page</a>, the configuration of your A/B and other tests is done by logging into your account on the VWO website.',
    array(':url' => $vwo_settings->toString())
  );

  return '<p>' . implode('</p><p>', $h) . '</p>';
}

/**
 * Help page for the visibility/inclusion page.
 */
function _visual_website_optimizer_help_visibility() {

  $h[] = t('VWO adds javascript to each page by default. If you are only running tests on parts of your site, or only wish to include certain users in your tests based on Drupal settings, you may filter which pages the javascritpt is added to with these filters.');
  $h[] = t('If more than one setting on this page is set, they are treated as <em>BOOLEAN AND</em>.');
  $h[] = t('The JS code can be added to pages at any stage of the render by calling <em>visual_website_optimizer_include_js()</em> from your own custom module. Please see the examples directory for a template of one way to doing this.');

  return '<p>' . implode('</p><p>', $h) . '</p>';
}

/**
 * Help page for the Extract VWO ID page.
 */
function _visual_website_optimizer_help_vwoid() {

  list($vwo_url_partner, $vwo_url_nopartner) = _visual_website_optimizer_help_links();

  $h[] = t('Your VWO Account ID can be extracted from the "VWO Smart Code" available from the VWO site.');
  $hl[] = t('Log into your Visual Website Optimizer account.');
  $hl[] = t('Locate the <em><strong>Settings</strong></em> menu down the left side of the site.');
  $hl[] = t('Click on <em><strong>Smart Code</strong></em> located along the top tabs.');
  $hl[] = t('Copy and paste the <em><strong>VWO Smart Code</strong></em> contents into the text box below.');
  $h[] = t('This can be found by following these simple directions:')
    . '<ul><li>' . implode('</li><li>', $hl) . '</li></ul>';

  $h[] = t('In order to use this module, you will need a Visual Website Optimizer account. A Free 30 day trial account is available from the VWO webite by clicking on either of the links below. To contribute towards the continued development of this module for free and with no effort, click on the affilitate link. Otherwise, click the other link without the affiliate marker.');
  $h2[] = t(
    '<a href=":url">Sign up for a Free 30 day trial account AND help out</a>',
    array(':url' => $vwo_url_partner->toString())
  );
  $h2[] = ', ' . t('or') . ' ';
  $h2[] = t(
    '<a href=":url">Sign up for a Free 30 day trial without affiliate link.</a>',
    array(':url' => $vwo_url_nopartner->toString())
  );
  $h[] = implode('', $h2);

  return '<p>' . implode('</p><p>', $h) . '</p>';
}

/**
 * Helper function to return the partner and noparter links to VWO.
 */
function _visual_website_optimizer_help_links() {

  $vwo_url_partner = Url::fromUri(
    'https://vwo.com/free-trial/',
    array(
      'absolute' => TRUE,
      'query' => array(
        'partner' => 'WSDRLBXN',
      ),
    )
  );

  $vwo_url_nopartner = Url::fromUri(
    'https://vwo.com/free-trial/',
    array(
      'absolute' => TRUE,
    )
  );

  return array($vwo_url_partner, $vwo_url_nopartner);
}
