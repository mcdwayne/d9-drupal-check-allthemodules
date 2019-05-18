<?php

namespace Drupal\external_link_change\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a filter to change the external links.
 *
 * @Filter(
 *   id = "external_link_change",
 *   title = @Translation("External link change"),
 *   description = @Translation("Adds prefix and suffixes to external URLs."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *     "domain" = "test",
 *     "prefix" = "abc",
 *     "suffix" = "&abc"
 *   }
 * )
 */
class ExternalLinkChange extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {

    // Prepare protocols pattern for absolute URLs.
    // \Drupal\Component\Utility\UrlHelper::stripDangerousProtocols() will
    // replace any bad protocols with HTTP, so we need to support the identical
    // list.
    // While '//' is technically optional for MAILTO only, we cannot cleanly
    // differ between protocols here without hard-coding MAILTO, so '//' is
    // optional for all protocols.
    // @see \Drupal\Component\Utility\UrlHelper::stripDangerousProtocols()
    $protocols = \Drupal::getContainer()->getParameter('filter_protocols');
    $protocols = implode(':(?://)?|', $protocols) . ':(?://)?';

    // Prepare domain name pattern.
    // The ICANN seems to be on track towards accepting more diverse top level
    // domains, so this pattern has been "future-proofed" to allow for TLDs of
    // length 2-64.
    $domain = '(?:[A-Za-z0-9._+-]+\.)?[A-Za-z]{2,64}\b';
    $ip = '(?:[0-9]{1,3}\.){3}[0-9]{1,3}';
    $auth = '[a-zA-Z0-9:%_+*~#?&=.,/;-]+@';
    $trail = '[a-zA-Z0-9:%_+*~#&\[\]=/;?!\.,-]*[a-zA-Z0-9:%_+*~#&\[\]=/;-]';
    $punctuation = '[\.,?!]*?';
    $avoid = "((?:$protocols)www)";

    // Get the value of domains, prefix and suffix.
    $suffix = $this->settings['suffix'];
    $prefix = $this->settings['prefix'];
    $domain_replace = $this->settings['domain'];
    $domains = explode(',', $domain_replace);
    $prefix = explode(',', $prefix);
    $suffix = explode(',', $suffix);

    // Match absolute URLs.
    $url_pattern = "(?:$auth)?(?:$domain|$ip)/?(?:$trail)?";

    // Match www domains.
    $url_pattern2 = "www\.(?:$domain)/?(?:$trail)?";

    $reg_exUrl = "`((?:$protocols)(?:$url_pattern))|($url_pattern2)($punctuation)`";

    $text = preg_replace_callback(
        $reg_exUrl, function ($matches) use ($prefix, $suffix, $avoid, $domains) {
                                        return external_link_change_apply_prefix_suffix($matches[0], $prefix, $suffix, $avoid, FALSE, $domains);
        }, $text
    );

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $form['domain'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Domains to use'),
      '#description' => $this->t('Add a comma separated list of domains.'),
      '#default_value' => $this->settings['domain'],
    );
    // Settings for prefix.
    $form['prefix'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Prefix text'),
      '#description' => $this->t('Add a comma separated list of prefixes corresponding to respective domain names. Enter prefix values equal to entered domain values. Enter the null at the position where you do not want to add prefix.'),
      '#default_value' => $this->settings['prefix'],
    );
    // Settings for suffix.
    $form['suffix'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Suffix text'),
      '#description' => $this->t('Add a comma separated list of suffixes corresponding to respective domain names. Enter suffix values equal to entered domain values. Enter the null at the position where you do not want to add suffix.'),
      '#default_value' => $this->settings['suffix'],
    );
    return $form;
  }

}
