<?php

namespace Drupal\iubenda_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures iubenda_integration settings.
 */
class IubendaSettingsCookieForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'iubenda_integration_settings_cookie';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'iubenda_integration.settings.cookie',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $iubenda_config = $this->config('iubenda_integration.settings');

    $cookie_enabled = $iubenda_config
      ->get('iubenda_integration_cookie_policy_enabled');

    $form['iubenda_integration_cookie_policy_enabled'] = array(
      '#title' => t('Enable EU Cookie Policy'),
      '#type' => 'checkbox',
      '#default_value' => $cookie_enabled,
    );

    $form['settings'] = array(
      '#type' => 'vertical_tabs',
      '#title' => t('Iubenda cookie policy'),
      '#states' => array(
          // Only show this field when the 'iubenda_integration_cookie_policy_enabled' checkbox is enabled.
          'visible' => array(
            ':input[name="iubenda_integration_cookie_policy_enabled"]' => array('checked' => TRUE),
          ),
        ),
    );

    // Iubenda cookie general settings tab.
    $form['iubenda_cookie'] = array(
      '#type' => 'details',
      '#title' => t('General'),
      '#group' => 'settings',
    );
    $form['iubenda_cookie']['siteId'] = array(
      '#title' => t('Site ID'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#size' => 10,
      '#default_value' => $iubenda_config->get('siteId'),
      '#description' => t('Id of the site (note: this ID is used to share the preferences of various cookie policies in multiple languages that are however connected to the same site/app)'),
    );
    $form['iubenda_cookie']['cookiePolicyId'] = array(
      '#title' => t('Cookie Policy ID'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#size' => 10,
      '#default_value' => $iubenda_config->get('cookiePolicyId'),
      '#description' => t('Id of your cookie policy'),
    );
    $form['iubenda_cookie']['cookiePolicyUrl'] = array(
      '#title' => t('Cookie Policy URL'),
      '#type' => 'textfield',
      '#default_value' => $iubenda_config->get('cookiePolicyUrl'),
      '#description' => t('The URL of your cookie policy, available in your dashboard, or from any page that hosts the policy.'),
    );
    $form['iubenda_cookie']['cookiePolicyInOtherWindow'] = array(
      '#title' => t('Cookie Policy in other window'),
      '#type' => 'checkbox',
      '#default_value' => $iubenda_config->get('cookiePolicyInOtherWindow'),
      '#description' => t('Set to true if you want the cookie policy to open in another window and not in the lightbox/iubenda modal.'),
    );
    $form['iubenda_cookie']['enableRemoteConsent'] = array(
      '#title' => t('Enable remote consent'),
      '#type' => 'checkbox',
      '#default_value' => $iubenda_config->get('enableRemoteConsent'),
      '#description' => t('Set to false to avoid the registration of consent "cross-site" on the domain iubenda.com.'),
    );
    $form['iubenda_cookie']['consentOnScroll'] = array(
      '#title' => t('Consent on scroll'),
      '#type' => 'checkbox',
      '#default_value' => $iubenda_config->get('consentOnScroll'),
      '#description' => t('Set to false to avoid the registration of consent at the scrolling of the page.'),
    );
    $form['iubenda_cookie']['reloadOnConsent'] = array(
      '#title' => t('Reload on consent'),
      '#type' => 'checkbox',
      '#default_value' => $iubenda_config->get('reloadOnConsent'),
      '#description' => t('Set to true if you want to reload the page when the user gives his consent.'),
    );
    $form['iubenda_cookie']['consentOnButton'] = array(
      '#title' => t('Consent on button'),
      '#type' => 'checkbox',
      '#default_value' => $iubenda_config->get('consentOnButton'),
      '#description' => t('Allows to activate the cookie policy also via clicking on the buttons button present in the page, other than the links.'),
    );
    $form['iubenda_cookie']['localConsentDomain'] = array(
      '#title' => t('Load consent domain'),
      '#type' => 'textfield',
      '#default_value' => $iubenda_config->get('reloadOnConsent'),
      '#description' => t('The domain on which you want the consent given by the user saved. If this parameter isn’t given, the consent gets saved by default in a cookie for the domain on a second level of the current page (for example: visiting www.example.com, the consent gets saved in a cookie on the on the domain .example.com). In the case in which the default behavior isn’t suitable, for example if the site were www.paesaggiurbani.italia.it and the consent needs to be saved for paesaggiurbani.italia.it, you need to fill that parameter with ‘paesaggiurbani.italia.it’.'),
    );
    $form['iubenda_cookie']['localConsentPath'] = array(
      '#title' => t('Load consent path'),
      '#type' => 'textfield',
      '#default_value' => $iubenda_config->get('localConsentPath'),
      '#description' => t('The path at which you’ll want, on the local domain, the consent by the user saved. By default, the consent given by the user gets saved on the local domain in a cookie on the path ‘/’. In this way, the cookie is available at whichever page of the domain that a user accesses. If on the other hand one would like to not make the preference cookie set for www.example.com/user1 available on www.example.com/user2, and vice versa, you’ll need to set this parameter to the calue ‘/user1’ in the first case and the value to ‘/user2’ in the second.'),
    );

    // Banner tab.
    $form['iubenda_cookie_banner'] = array(
      '#type' => 'details',
      '#title' => t('Banner'),
      '#group' => 'settings',
    );
    $form['iubenda_cookie_banner']['slideDown'] = array(
      '#title' => t('Slide down'),
      '#type' => 'checkbox',
      '#default_value' => $iubenda_config->get('slideDown'),
      '#description' => t('Set to false to remove the initial banner animation'),
    );
    $form['iubenda_cookie_banner']['zIndex'] = array(
      '#title' => t('zIndex'),
      '#type' => 'textfield',
      '#default_value' => $iubenda_config->get('zIndex'),
      '#description' => t('zIndex of the div of the banner of the cookie policy (default value: 99999998).'),
    );
    $form['iubenda_cookie_banner']['content'] = array(
      '#title' => t('Content'),
      '#type' => 'textarea',
      '#default_value' => $iubenda_config->get('content'),
      '#description' => t('content (text) with the notice of the cookie policy (current default:"<pre>
  Notice

  This website or its third party tools use cookies, which are necessary to its functioning and required to achieve the purposes illustrated in the %{cookie_policy_link}. If you want to know more or withdraw your consent to all or some of the cookies, please refer to the cookie policy.
  By closing this banner, scrolling this page, clicking a link or continuing to browse otherwise, you agree to the use of cookies.

  </pre>", where %{cookie_policy_link} is the placeholder for the cookie policy link).'),
    );
    $form['iubenda_cookie_banner']['cookiePolicyLinkCaption'] = array(
      '#title' => t('Cookie Policy link caption'),
      '#type' => 'textfield',
      '#default_value' => $iubenda_config->get('cookiePolicyLinkCaption'),
      '#description' => t('Anchor text of the link to the cookie policy (default value: "cookie policy").'),
    );
    $form['iubenda_cookie_banner']['backgroundColor'] = array(
      '#title' => t('Background color'),
      '#type' => 'textfield',
      '#default_value' => $iubenda_config->get('backgroundColor'),
      '#description' => t('(default "#000") background color of the banner.'),
    );
    $form['iubenda_cookie_banner']['textColor'] = array(
      '#title' => t('Text color'),
      '#type' => 'textfield',
      '#default_value' => $iubenda_config->get('textColor'),
      '#description' => t('(default "#fff") text color of the banner.'),
    );
    $form['iubenda_cookie_banner']['fontSize'] = array(
      '#title' => t('Font size'),
      '#type' => 'textfield',
      '#default_value' => $iubenda_config->get('fontSize'),
      '#description' => t('(default "14px") text size of the banner.'),
    );
    $form['iubenda_cookie_banner']['innerHtmlCloseBtn'] = array(
      '#title' => t('Close button - inner HTML'),
      '#type' => 'textfield',
      '#default_value' => $iubenda_config->get('innerHtmlCloseBtn'),
      '#description' => t('(default "x") closing button text for the banner.'),
    );
    $form['iubenda_cookie_banner']['applyStyles'] = array(
      '#title' => t('Apply styles'),
      '#type' => 'checkbox',
      '#default_value' => $iubenda_config->get('applyStyles'),
      '#description' => t('(default true) if set to false, then banner doesn’t have any style applied/CSS.'),
    );
    $form['iubenda_cookie_banner']['html'] = array(
      '#title' => t('HTML'),
      '#type' => 'textarea',
      '#default_value' => $iubenda_config->get('html'),
      '#description' => t('HTML of the banner to substitute the default version. NOTE: the following elements are necessary for the correct functioning of the banner:
  <ul>
  <li>div.iubenda-cs-content [main content]</li>
  <li>a.iubenda-cs-cookie-policy-lnk [link with href set to the cookie policy, i.e. https://www.iubenda.com/privacy-policy/417383/cookie-policy?an=no&s_ck=false]</li>
  <li>a.iubenda-cs-close-btn [close button for the banner]</li>
  </ul>'),
    );

    // Footer tab.
    $form['iubenda_cookie_footer'] = array(
      '#type' => 'details',
      '#title' => t('Footer'),
      '#group' => 'settings',
    );
    $form['iubenda_cookie_footer']['message'] = array(
      '#title' => t('Message'),
      '#type' => 'textarea',
      '#default_value' => $iubenda_config->get('message'),
      '#description' => t('Text message visualized below the details of the cookie policy (default value: "By continuing to browse or by closing this window, you accept the use of cookies.").'),
    );

    $form['iubenda_cookie_footer']['btnCaption'] = array(
      '#title' => t('Button caption'),
      '#type' => 'textfield',
      '#default_value' => $iubenda_config->get('btnCaption'),
      '#description' => t('Text message inserted in the button to confirm and consent to the cookie policy (default value: "Continue to browse").'),
    );

    // Callback tab.
    $form['iubenda_cookie_callback'] = array(
      '#type' => 'details',
      '#title' => t('Callback'),
      '#group' => 'settings',
    );
    $form['iubenda_cookie_callback']['onBannerShown'] = array(
      '#title' => t('On banner shown'),
      '#type' => 'textfield',
      '#default_value' => $iubenda_config->get('onBannerShown'),
      '#description' => t('function – called when the banner is shown.'),
    );
    $form['iubenda_cookie_callback']['onConsentGiven'] = array(
      '#title' => t('On consent given'),
      '#type' => 'textfield',
      '#default_value' => $iubenda_config->get('onConsentGiven'),
      '#description' => t('function – called at the moment when the user consents to the cookie policy and therefore to the use of cookies.'),
    );

    // Preference cookie tab.
    $form['iubenda_cookie_preference'] = array(
      '#type' => 'details',
      '#title' => t('Preference Cookie'),
      '#group' => 'settings',
    );
    $form['iubenda_cookie_preference']['expireAfter'] = array(
      '#title' => t('Expire after'),
      '#type' => 'textfield',
      '#default_value' => $iubenda_config->get('expireAfter'),
      '#description' => t('(default 365) number of days that the consent will remain valid for this website.'),
    );

    // Parameters for developers tab.
    $form['iubenda_cookie_developers'] = array(
      '#type' => 'details',
      '#title' => t('Parameters for developers'),
      '#group' => 'settings',
    );
    $form['iubenda_cookie_developers']['logLevel'] = array(
      '#title' => t('Log level'),
      '#type' => 'select',
      '#options' => array(
        '' => t('- Select -'),
        'debug' => t('Debug'),
        'info' => t('info'),
        'warn' => t('warn'),
        'error' => t('error'),
        'fatal' => t('fatal'),
      ),
      '#default_value' => $iubenda_config->get('logLevel'),
    );
    $form['iubenda_cookie_developers']['skipSaveConsent'] = array(
      '#title' => t('Skip save consent'),
      '#type' => 'checkbox',
      '#default_value' => $iubenda_config->get('skipSaveConsent'),
      '#description' => t('true (default value) if the consent doesn’t need to be saved in the preference cookies.'),
    );
    $form['iubenda_cookie_developers']['logViaAlert'] = array(
      '#title' => t('Log via alert'),
      '#type' => 'checkbox',
      '#default_value' => $iubenda_config->get('logViaAlert'),
      '#description' => t('default value: false. True if the logging needs to be shown via alert.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')
      ->getEditable('iubenda_integration.settings');

    // Save settings.
    foreach ($form_state->getValues() as $key => $value) {
      //drupal_set_message($key . ': ' . $value);
      $config->set($key, $form_state->getValue($key))->save();
    }

    parent::submitForm($form, $form_state);
  }

}
