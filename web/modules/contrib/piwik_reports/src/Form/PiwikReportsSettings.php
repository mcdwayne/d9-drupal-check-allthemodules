<?php

namespace Drupal\piwik_reports\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\piwik_reports\PiwikData;
use GuzzleHttp\Exception\RequestException;

/**
 * Class PiwikReportsSettings.
 */
class PiwikReportsSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'piwik_reports.piwikreportssettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'piwik_reports_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('piwik_reports.piwikreportssettings');
    $form['piwik_reports_server'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Piwik report server'),
    ];
    $form['piwik_reports_server']['piwik_server_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Piwik Server URL'),
      '#description' => $this->t('The URL to your Piwik base directory, e.g., &quot;https://analytics.example.com/piwik/&quot;.'),
      '#maxlength' => 255,
      '#size' => 80,
      '#default_value' => $config->get('piwik_server_url'),
    ];
    $form['token_auth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Token auth'),
      '#description' => $this->t('To see piwik reports in Drupal you need a <strong>token_auth</strong> value. You can find it in the  <strong>Users</strong> tab under the <strong>Settings</strong> link in your Piwik account or ask your Piwik server administrator.'),
    ];
    $form['token_auth']['piwik_reports_token_auth'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Piwik authentication string'),
      '#description' => $this->t('Leave blank if you prefer each user setting their own, or paste it here to have a global <strong>token_auth</strong>. If anonymous users have view permissions in Piwik you can set this value to <strong>anonymous</strong>. Users still need &quot;Access Piwik reports&quot; permission to see the reports in Drupal.'),
      '#maxlength' => 40,
      '#size' => 40,
      '#default_value' => $config->get('piwik_reports_token_auth'),
    ];
    $form['piwik_reports_sites'] = [
      '#type' => 'details',
      '#title' => $this->t('Allowed sites'),
      '#description' => $this->t('List sites you want restrict your users access to.'),
    ];
    $sites = PiwikData::getSites($config->get('piwik_reports_token_auth'));
    $allowed_sites_desc = $this->t('List accessible sites id separated by a comma. Example: &quot;1,4,12&quot;. Leave blank to let users see all sites accessible on piwik server with current token auth (highly recommended in case of per user token auth).');
    if (is_array($sites) && count($sites)) {
      if ($config->get('piwik_reports_token_auth')) {
        $allowed_sites_desc .= ' ' . $this->t('Sites currently accessible with global token_auth are:');
      }
      else {
        $allowed_sites_desc .= ' ' . $this->t('Sites current accessible as anonymous are:');
      }
      foreach ($sites as $site){
        $allowed_sites_desc .= '<br />' . (int) $site['idsite'] . ' - ' . Html::escape($site['name']);
      }
    }
    else {
      $allowed_sites_desc .= ' ' . $this->t('No accessible sites are available with current global token auth. Please check your token auth is correct and that it has view permission on Piwik server.');
    }
    $form['piwik_reports_sites']['piwik_reports_allowed_sites'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed sites'),
      '#description' => $allowed_sites_desc,
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('piwik_reports_allowed_sites'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $url = $form_state->getValue('piwik_server_url');
    if (!empty($url)) {

      if (substr($url,-1) !== '/') {
        $url .= '/';
        $form_state->setValueForElement($form['piwik_reports_server']['piwik_server_url'], $url);
      }
      $url = $url . 'piwik.php';
      try {
        $result = \Drupal::httpClient()->get($url);
        if ($result->getStatusCode() != 200) {
          $form_state->setErrorByName('piwik_server_url', $this->t('The validation of "@url" failed with error "@error" (HTTP code @code).', [
            '@url' => UrlHelper::filterBadProtocol($url),
            '@error' => $result->getReasonPhrase(),
            '@code' => $result->getStatusCode(),
          ]));
        }
      }
      catch (RequestException $exception) {
        $form_state->setErrorByName('piwik_server_url', $this->t('The validation of "@url" failed with an exception "@error" (HTTP code @code).', [
          '@url' => UrlHelper::filterBadProtocol($url),
          '@error' => $exception->getMessage(),
          '@code' => $exception->getCode(),
        ]));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('piwik_reports.piwikreportssettings')
      ->set('piwik_report_server', $form_state->getValue('piwik_report_server'))
      ->set('piwik_server_url', $form_state->getValue('piwik_server_url'))
      ->set('token_auth', $form_state->getValue('token_auth'))
      ->set('piwik_reports_token_auth', $form_state->getValue('piwik_reports_token_auth'))
      ->set('piwik_reports_sites', $form_state->getValue('piwik_reports_sites'))
      ->set('piwik_reports_allowed_sites', $form_state->getValue('piwik_reports_allowed_sites'))
      ->save();
  }

}
