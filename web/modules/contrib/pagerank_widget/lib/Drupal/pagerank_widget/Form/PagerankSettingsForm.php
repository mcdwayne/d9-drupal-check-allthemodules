<?php

/**
 * @file
 * Contains \Drupal\pagerank_widget\Form\PagerankSettingsForm.
 */

namespace Drupal\pagerank_widget\Form;

use Drupal\Core\Form\ConfigFormBase;

class PagerankSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'pagerank_widget_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('pagerank_widget.settings');
    $last_refresh = $config->get('next_execution') - $config->get('interval');
    // Execution time has to be reset to force an instant cron run.
    $config->set('next_execution', 0);
    // To find a cron call here looks odd, but it's the only way to have any
    // changed variables in the form being processed in the hook_cron(). After
    // submitting the form you come back on the same form and that's when all
    // new variables are available. The only drawback is that cron runs twice
    // (once at the first form load and once at the second), but that's not a
    // big deal.
    drupal_cron_run();
    // Essential to have some credentials.
    $api_key = trim($config->get('api_key'));
    $monitor_id = trim($config->get('monitor_id'));
    // Where to find the all-time pagerank ratio.
    $url = "http://api.uptimerobot.com/getMonitors?apiKey=" . $api_key . "&monitors=" . $monitor_id . "&format=xml";

    $form['pr_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('PageRank'),
    );

    $form['pr_settings']['pagerank_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#default_value' => $config->get('enabled'),
      '#description' => t('Disabling pauses the monitor until re-enabling and removes the ratio display. Disable pagerank when your site might go down temporarily,for example during development, or if you want to use only the copyright notice.'),
    );

   $form['pr_settings']['pagerank_string'] = array(
      '#type' => 'textfield',
      '#title' => t('Text'),
      '#default_value' => $config->get('string'),
      '#description' => t('String to use. Suggestions "PR", "Google PageRank", "PageRank" (default).'),
    );

    $form['pr_settings']['pagerank_suffix'] = array(
      '#type' => 'textfield',
      '#title' => t('Suffix'),
      '#default_value' => $config->get('suffix'),
      '#description' => t('String to append. Suggestions " / 10", " out of 10" or nothing (default). <strong>Do not forget desired spaces.</strong>'),
    );

    $form['pr_settings']['pagerank_link'] = array(
      '#type' => 'textfield',
      '#title' => t('Link to a third party PR check'),
      '#default_value' => $config->get('link'),
      '#description' => t('Leave empty to disable, but the reliability of the widget is perceived better it leads to a third party PR check (default: "http://domaintyper.com/PageRankCheck/").<br /><strong>Use only services that accept a trailing domain in the URL to query. Do not forget the trailing slash or "?q="</strong>, depending on the used service.'),
    );

    // Grabbing the pagerank ratio once a day is good enough, but leave it up to
    // the site owner to decide. Second option is the actual set cron interval.
    $form['pr_settings']['pagerank_interval'] = array(
      '#type' => 'radios',
      '#title' => t('Refresh interval'),
      '#options' => array(
        86400 => t('24 hours (recommended)'),
        0 => t('(every cron run)'),
        ),
      '#default_value' => $config->get('interval'),
      '#description' => t('Saving this form refreshes the pagerank ratio instantly, independent from this setting. Last refresh was') . ' ' . t('@interval ago', array('@interval' => format_interval((REQUEST_TIME - $last_refresh)))) . '.',
      '#required' => TRUE,
    );

    $form['pagerank_notice'] = array(
      '#type' => 'fieldset',
      '#title' => t('Copyright notice'),
    );

    $form['pagerank_notice']['pagerank_notice_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enabled'),
      '#default_value' => $config->get('notice_enabled'),
    );

    // For the examples we use real data.
    // Current domain name without the leading protocol.
    global $base_url;
    $year = $config->get('year');
    $notice = $config->get('prepend') . ' © ' . (($year != date('Y') && !empty($year)) ? $year . '-' . date('Y') : date('Y'));
    $site_config = config('system.site');
    $form['pagerank_notice']['pagerank_url_name'] = array(
      // Create different types of notices to choose from.
      '#type' => 'radios',
      '#title' => t('Choose a notice'),
      '#options' => array(
        $base_url => '<strong>' . $notice . ' ' . $base_url . '</strong> ' . t('(Base url. Default.)'),
        $site_config->get('name') => '<strong>' . $notice . ' ' . $site_config->get('name') . '</strong> ' . t("(Site name. Preferable if the site name is a person's full name or a company name.)"),
      ' ' => '<strong>' . $notice . '</strong> ' . t('(Leaving out the designation of owner is not recommended.)'),
      ),
      '#default_value' => $config->get('url_name'),
      '#description' => t("'Year of first publication' is not used until entered below, for example © 2009-") . date('Y') . '. ' . t('Save this form to refresh above examples.'),
    );

    $form['pagerank_notice']['pagerank_year'] = array(
      '#type' => 'textfield',
      '#title' => t('What year was the domain first online?'),
      '#default_value' => $year,
      '#description' => t("Leave empty to display only the current year (default). Also if the 'starting year' equals the 'current year' only one will be displayed until next year.<br />To play safe legally, it's best to enter a 'Year of first publication', although copyright is in force even without any notice."),
      '#size' => 4,
      '#maxlength' => 4,
    );
  
    $form['pagerank_notice']['pagerank_prepend'] = array(
      '#type' => 'textfield',
      '#title' => t('Prepend text'),
      '#default_value' => trim($config->get('prepend')),
      '#description' => t("For example 'All images' on a photographer's website."),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Validate Uptime Widget settings submission.
   */
  public function validateForm(array &$form, array &$form_state) {
    // Before 1991 there was no world wide web and the future can't be a
    // 'year of first publication' but it can be left empty.
    $limit = $form_state['values']['pagerank_year'];
    if ((!is_numeric($limit) || $limit < 1991 || $limit > date('Y')) && !empty($limit)) {
      form_set_error('pagerank_year', '<strong>' . t('INVALID YEAR.') . '</strong>');
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('pagerank_widget.settings')
      ->set('enabled', $form_state['values']['pagerank_enabled'])
      ->set('string', $form_state['values']['pagerank_string'])
      ->set('suffix', $form_state['values']['pagerank_suffix'])
      ->set('link', $form_state['values']['pagerank_link'])
      ->set('interval', $form_state['values']['pagerank_interval'])
      ->set('notice_enabled', $form_state['values']['pagerank_notice_enabled'])
      ->set('year', $form_state['values']['pagerank_year'])
      ->set('prepend', $form_state['values']['pagerank_prepend'])
      ->set('url_name', $form_state['values']['pagerank_url_name'])
    ->save();
    drupal_flush_all_caches();

    parent::submitForm($form, $form_state);
  }

}

