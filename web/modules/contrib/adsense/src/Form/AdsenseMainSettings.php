<?php

namespace Drupal\adsense\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

use Drupal\adsense\AdsenseAdBase;

/**
 * Class AdsenseMainSettings.
 */
class AdsenseMainSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adsense_main_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['adsense.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    module_load_include('inc', 'adsense', 'help/adsense.help');
    module_load_include('inc', 'adsense', 'includes/adsense.search_options');

    $config = $this->config('adsense.settings');

    $form['help'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Help and instructions'),
    ];

    $form['help']['help'] = ['#markup' => adsense_help_text()];

    $form['adsense_basic_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site Google AdSense Publisher ID'),
      '#required' => TRUE,
      '#default_value' => $config->get('adsense_basic_id'),
      '#pattern' => 'pub-[0-9]+',
      '#description' => $this->t('This is the Google AdSense Publisher ID for the site owner. It is used if no other ID is suitable. Get this in your Google Adsense account. It should be similar to %id.',
        ['%id' => 'pub-9999999999999']
      ),
    ];

    $options = $this->adsenseIdSettingsClientIdMods();
    if (count($options) > 1) {
      $form['adsense_id_module'] = [
        '#type' => 'radios',
        '#title' => $this->t('Publisher ID module'),
        '#default_value' => $config->get('adsense_id_module'),
        '#options' => $options,
      ];
    }
    else {
      $form['adsense_id_module'] = [
        '#type' => 'hidden',
        '#value' => 'adsense_basic',
      ];
    }

    $form['adsense_unblock_ads'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display anti ad-block request?'),
      '#default_value' => $config->get('adsense_unblock_ads'),
      '#description' => $this->t("EXPERIMENTAL! Enabling this feature will add a mechanism that tries to detect when adblocker software is in use, displaying a polite request to the user to enable ads on this site. [@moreinfo]",
        ['@moreinfo' => Link::fromTextAndUrl($this->t('More information'), Url::fromUri('https://easylist.to/2013/05/10/anti-adblock-guide-for-site-admins.html'))->toString()]
      ),
    ];

    $form['adsense_test_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable test mode?'),
      '#default_value' => $config->get('adsense_test_mode'),
      '#description' => $this->t('This enables you to test the AdSense module settings. This can be useful in some situations: for example, testing whether revenue sharing is working properly or not without having to display real ads on your site. It is best to test this after you log out.'),
    ];

    $form['adsense_disable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Google AdSense ads?'),
      '#default_value' => $config->get('adsense_disable'),
      '#description' => $this->t('This disables all display of Google AdSense ads from your web site. This is useful in certain situations, such as site upgrades, or if you make a copy of the site for development and test purposes.'),
    ];

    $form['adsense_placeholder'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Placeholder when ads are disabled?'),
      '#default_value' => $config->get('adsense_placeholder'),
      '#description' => $this->t('This causes an empty box to be displayed in place of the ads when they are disabled.'),
    ];

    $form['adsense_placeholder_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Placeholder text to display'),
      '#default_value' => $config->get('adsense_placeholder_text'),
      '#rows' => 3,
      '#description' => $this->t('Enter any text to display as a placeholder when ads are disabled.'),
    ];

    $form['secret'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Undocumented options'),
      '#description' => $this->t("Warning: Use of these options is AT YOUR OWN RISK. Google will never generate an ad with any of these options, so using one of them is a violation of Google AdSense's Terms and Conditions. USE OF THESE OPTIONS MAY RESULT IN GETTING BANNED FROM THE PROGRAM. You may lose all the revenue accumulated in your account. FULL RESPONSIBILITY FOR THE USE OF THESE OPTIONS IS YOURS. In other words, don't complain to the authors about getting banned, even if using one of these options was provided as a solution to a reported problem."),
    ];

    $form['secret']['agreed'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('I agree'),
    ];

    $form['secret']['agreed']['adsense_secret_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Language to display ads'),
      '#default_value' => $config->get('adsense_secret_language'),
      '#options' => array_merge([
        '' => 'Set by Google',
      ], AdsenseAdBase::adsenseLanguages()),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('adsense.settings');
    $form_state->cleanValues();

    foreach ($form_state->getValues() as $key => $value) {
      $config->set($key, Html::escape($value));
    }
    $config->save();
  }

  /**
   * Search for the available Publisher ID modules.
   *
   * @return array
   *   array of selectable Publisher ID functions
   */
  private function adsenseIdSettingsClientIdMods() {
    // TODO ModuleHandler::getImplementations.
    $ret['adsense_basic'] = 'Always use the site Publisher ID.';

    $funcs = get_defined_functions();
    foreach ($funcs['user'] as $func) {
      if (preg_match('!_adsense$!', $func)) {
        $settings = $func('settings');
        $ret[$func] = $settings['name'];
        if (!empty($settings['desc'])) {
          $ret[$func] .= "<div style='margin-left: 2.5em;' class='description'>{$settings['desc']}</div>";
        }
      }
    }
    return $ret;
  }

}
