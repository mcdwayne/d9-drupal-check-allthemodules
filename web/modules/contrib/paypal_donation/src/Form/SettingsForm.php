<?php

namespace Drupal\paypal_donation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PayPalDonationSettingsForm.
 *
 * @package Drupal\paypal_donation\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'paypal_donation.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pay_pal_donation_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('paypal_donation.settings');

    $form['welcome_text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Welcome text'),
      '#default_value' => $config->get('welcome_text'),
      '#description' => $this->t('Text which will be shown on the donation form page.'),
    ];
    $form['success_text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Success text'),
      '#default_value' => $config->get('success_text'),
      '#description' => $this->t('Text which will be shown after successful donation.'),
      '#required' => TRUE,
    ];
    $form['fail_text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Cancel text'),
      '#default_value' => $config->get('fail_text'),
      '#description' => $this->t('Text which will be shown after unsuccessful/canceled donation.'),
      '#required' => TRUE,
    ];
    $form['sandbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sandbox?'),
      '#default_value' => $config->get('sandbox'),
      '#description' => $this->t('Send donations to Sandbox account.'),
    ];
    $form['lc'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#default_value' => $config->get('lc'),
      '#description' => $this->t('Language in which paypal payment page will be shown.'),
      '#options' => [
        "en_AL" => "Albania - English",
        "en_DZ" => "Algeria - English",
        "en_AD" => "Andorra - English",
        "en_AO" => "Angola - English",
        "en_AI" => "Anguilla - English",
        "en_AG" => "Antigua and Barbuda - English",
        "en_AR" => "Argentina - English",
        "en_AM" => "Armenia - English",
        "en_AW" => "Aruba - English",
        "en_AU" => "Australia - Australian English",
        "de_AT" => "Austria - German",
        "en_AT" => "Austria - English",
        "en_AZ" => "Azerbaijan Republic - English",
        "en_BS" => "Bahamas - English",
        "en_BH" => "Bahrain - English",
        "en_BB" => "Barbados - English",
        "en_BY" => "Belarus - English",
        "en_BE" => "Belgium - English",
        "nl_BE" => "Belgium - Dutch",
        "fr_BE" => "Belgium - French",
        "en_BZ" => "Belize - English",
        "en_BJ" => "Benin - English",
        "en_BM" => "Bermuda - English",
        "en_BT" => "Bhutan - English",
        "en_BO" => "Bolivia - English",
        "en_BA" => "Bosnia and Herzegovina - English",
        "en_BW" => "Botswana - English",
        "pt_BR" => "Brazil - Portuguese",
        "en_BR" => "Brazil - English",
        "en_BN" => "Brunei - English",
        "en_BG" => "Bulgaria - English",
        "en_BF" => "Burkina Faso - English",
        "en_BI" => "Burundi - English",
        "en_KH" => "Cambodia - English",
        "en_CM" => "Cameroon - English",
        "en_CA" => "Canada - English",
        "fr_CA" => "Canada - French",
        "en_CV" => "Cape Verde - English",
        "en_KY" => "Cayman Islands - English",
        "en_TD" => "Chad - English",
        "en_CL" => "Chile - English",
        "zh_C2" => "China - Simplified Chinese",
        "en_C2" => "China - English",
        "en_CO" => "Colombia - English",
        "en_KM" => "Comoros - English",
        "en_CK" => "Cook Islands - English",
        "en_CR" => "Costa Rica - English",
        "en_CI" => "Cote D'Ivoire - English",
        "en_HR" => "Croatia - English",
        "en_CY" => "Cyprus - English",
        "en_CZ" => "Czech Republic - English",
        "en_CD" => "Democratic Republic of the Congo - English",
        "da_DK" => "Denmark - Danish",
        "en_DK" => "Denmark - English",
        "en_DJ" => "Djibouti - English",
        "en_DM" => "Dominica - English",
        "en_DO" => "Dominican Republic - English",
        "en_EC" => "Ecuador - English",
        "en_EG" => "Egypt - English",
        "en_SV" => "El Salvador - English",
        "en_ER" => "Eritrea - English",
        "en_EE" => "Estonia - English",
        "ru_EE" => "Estonia - Russian",
        "fr_EE" => "Estonia - French",
        "es_EE" => "Estonia - Spanish",
        "zh_EE" => "Estonia - Simplified Chinese",
        "en_ET" => "Ethiopia - English",
        "en_FK" => "Falkland Islands - English",
        "en_FO" => "Faroe Islands - English",
        "en_FJ" => "Fiji - English",
        "en_FI" => "Finland - English",
        "fr_FR" => "France - French",
        "en_FR" => "France - English",
        "en_GF" => "French Guiana - English",
        "en_PF" => "French Polynesia - English",
        "en_GA" => "Gabon Republic - English",
        "en_GM" => "Gambia - English",
        "en_GE" => "Georgia - English",
        "de_DE" => "Germany - German",
        "en_DE" => "Germany - English",
        "en_GI" => "Gibraltar - English",
        "en_GR" => "Greece - English",
        "en_GL" => "Greenland - English",
        "en_GD" => "Grenada - English",
        "en_GP" => "Guadeloupe - English",
        "en_GT" => "Guatemala - English",
        "en_GN" => "Guinea - English",
        "en_GW" => "Guinea Bissau - English",
        "en_GY" => "Guyana - English",
        "en_HN" => "Honduras - English",
        "en_HK" => "Hong Kong - English",
        "zh_HK" => "Hong Kong - Traditional Chinese",
        "en_HU" => "Hungary - English",
        "en_IS" => "Iceland - English",
        "en_IN" => "India - English",
        "en_ID" => "Indonesia - English",
        "en_IE" => "Ireland - English",
        "he_IL" => "Israel - Hebrew",
        "en_IL" => "Israel - English",
        "it_IT" => "Italy - Italian",
        "en_IT" => "Italy - English",
        "en_JM" => "Jamaica - English",
        "ja_JP" => "Japan - Japanese",
        "en_JP" => "Japan - English",
        "en_JO" => "Jordan - English",
        "en_KZ" => "Kazakhstan - English",
        "en_KE" => "Kenya - English",
        "en_KI" => "Kiribati - English",
        "en_KW" => "Kuwait - English",
        "en_KG" => "Kyrgyzstan - English",
        "en_LA" => "Laos - English",
        "en_LV" => "Latvia - English",
        "en_LS" => "Lesotho - English",
        "en_LI" => "Liechtenstein - English",
        "en_LT" => "Lithuania - English",
        "en_LU" => "Luxembourg - English",
        "en_MK" => "Macedonia - English",
        "en_MG" => "Madagascar - English",
        "en_MW" => "Malawi - English",
        "en_MY" => "Malaysia - English",
        "en_MV" => "Maldives - English",
        "en_ML" => "Mali - English",
        "en_MT" => "Malta - English",
        "en_MH" => "Marshall Islands - English",
        "en_MQ" => "Martinique - English",
        "en_MR" => "Mauritania - English",
        "en_MU" => "Mauritius - English",
        "en_YT" => "Mayotte - English",
        "es_MX" => "Mexico - Spanish",
        "en_MX" => "Mexico - English",
        "en_FM" => "Micronesia - English",
        "en_MD" => "Moldova - English",
        "en_MC" => "Monaco - English",
        "en_MN" => "Mongolia - English",
        "en_ME" => "Montenegro - English",
        "en_MS" => "Montserrat - English",
        "en_MA" => "Morocco - English",
        "en_MZ" => "Mozambique - English",
        "en_NA" => "Namibia - English",
        "en_NR" => "Nauru - English",
        "en_NP" => "Nepal - English",
        "nl_NL" => "Netherlands - Dutch",
        "en_NL" => "Netherlands - English",
        "en_AN" => "Netherlands Antilles - English",
        "en_NC" => "New Caledonia - English",
        "en_NZ" => "New Zealand - English",
        "en_NI" => "Nicaragua - English",
        "en_NE" => "Niger - English",
        "en_NG" => "Nigeria - English",
        "en_NU" => "Niue - English",
        "en_NF" => "Norfolk Island - English",
        "no_NO" => "Norway - Norwegian",
        "en_NO" => "Norway - English",
        "en_OM" => "Oman - English",
        "en_PW" => "Palau - English",
        "en_PA" => "Panama - English",
        "en_PG" => "Papua New Guinea - English",
        "en_PY" => "Paraguay - English",
        "en_PE" => "Peru - English",
        "en_PH" => "Philippines - English",
        "en_PN" => "Pitcairn Islands - English",
        "pl_PL" => "Poland - Polish",
        "en_PL" => "Poland - English",
        "pt_PT" => "Portugal - Portuguese",
        "en_PT" => "Portugal - English",
        "en_QA" => "Qatar - English",
        "en_CG" => "Republic of the Congo - English",
        "en_RE" => "Reunion - English",
        "en_RO" => "Romania - English",
        "ru_RU" => "Russia - Russian",
        "en_RU" => "Russia - English",
        "en_RW" => "Rwanda - English",
        "en_KN" => "Saint Kitts and Nevis Anguilla - English",
        "en_PM" => "Saint Pierre and Miquelon - English",
        "en_VC" => "Saint Vincent and Grenadines - English",
        "en_WS" => "Samoa - English",
        "en_SM" => "San Marino - English",
        "en_ST" => "São Tomé and Príncipe - English",
        "en_SA" => "Saudi Arabia - English",
        "en_SN" => "Senegal - English",
        "en_RS" => "Serbia - English",
        "en_SC" => "Seychelles - English",
        "en_SL" => "Sierra Leone - English",
        "en_SG" => "Singapore - English",
        "en_SK" => "Slovakia - English",
        "en_SI" => "Slovenia - English",
        "en_SB" => "Solomon Islands - English",
        "en_SO" => "Somalia - English",
        "en_ZA" => "South Africa - English",
        "en_KR" => "South Korea - English",
        "es_ES" => "Spain - Spanish",
        "en_ES" => "Spain - English",
        "en_LK" => "Sri Lanka - English",
        "en_SH" => "St. Helena - English",
        "en_LC" => "St. Lucia - English",
        "en_SR" => "Suriname - English",
        "en_SJ" => "Svalbard and Jan Mayen Islands - English",
        "en_SZ" => "Swaziland - English",
        "sv_SE" => "Sweden - Swedish",
        "en_SE" => "Sweden - English",
        "de_CH" => "Switzerland - German",
        "fr_CH" => "Switzerland - French",
        "en_CH" => "Switzerland - English",
        "zh_TW" => "Taiwan - Traditional Chinese",
        "en_TW" => "Taiwan - English",
        "en_TJ" => "Tajikistan - English",
        "en_TZ" => "Tanzania - English",
        "th_TH" => "Thailand - Thai",
        "en_TH" => "Thailand - English",
        "en_TG" => "Togo - English",
        "en_TO" => "Tonga - English",
        "en_TT" => "Trinidad and Tobago - English",
        "en_TN" => "Tunisia - English",
        "en_TM" => "Turkmenistan - English",
        "en_TC" => "Turks and Caicos Islands - English",
        "en_TV" => "Tuvalu - English",
        "en_UG" => "Uganda - English",
        "en_UA" => "Ukraine - English",
        "en_AE" => "United Arab Emirates - English",
        "en_GB" => "United Kingdom - English",
        "en_US" => "United States - English",
        "fr_US" => "United States - French",
        "es_US" => "United States - Spanish",
        "zh_US" => "United States - Simplified Chinese",
        "en_UY" => "Uruguay - English",
        "en_VU" => "Vanuatu - English",
        "en_VA" => "Vatican City State - English",
        "en_VE" => "Venezuela - English",
        "en_VN" => "Vietnam - English",
        "en_VG" => "Virgin Islands (British) - English",
        "en_WF" => "Wallis and Futuna Islands - English",
        "en_YE" => "Yemen - English",
        "en_ZM" => "Zambia - English",
        "en_ZW" => "Zimbabwe - English",
        "en_GB" => "International",

      ],
      '#required' => TRUE,
    ];
    $form['business'] = [
      '#type' => 'email',
      '#title' => $this->t('PayPal Business email'),
      '#default_value' => $config->get('business'),
      '#description' => $this->t('Email of your business account.'),
      '#required' => TRUE,
    ];
    $form['item_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organization name/service'),
      '#default_value' => $config->get('item_name'),
      '#required' => TRUE,
    ];
    $form['item_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Donation ID:'),
      '#default_value' => $config->get('item_number'),
      '#description' => $this->t('You can assign an Item ID as a unique identifier to make tracking easier.'),
      '#required' => TRUE,
    ];
    $form['amounts'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amounts:'),
      '#default_value' => $config->get('amounts'),
      '#description' => $this->t('Amounts which will show as radio buttons. Separate amounts with comma.'),
      '#required' => FALSE,
    ];
    $currencies = ['USD', 'AUD', 'BRL', 'GBP', 'CAD', 'CZK', 'DKK', 'EUR',
      'HKD', 'HUF', 'ILS', 'JPY', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN',
      'RUB', 'SGD', 'SEK', 'CHF', 'THB',
    ];
    $form['currency_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Currency:'),
      '#default_value' => $config->get('currency_code'),
      '#description' => $this->t('Defines in which currency donation should be made.'),
      '#options' => array_combine($currencies, $currencies),
      '#required' => TRUE,
    ];
    $form['allow_custom_amount'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow custom amount?'),
      '#default_value' => $config->get('allow_custom_amount'),
      '#description' => $this->t('Allow user to write his own amount to donate.'),
    ];
    $form['recurring'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Offer recurring donation?'),
      '#default_value' => $config->get('recurring'),
      '#description' => $this->t('If selected, user will be presented with the recurring payment options.'),
    ];
    $form['recurring_container'] = [
      '#type' => 'container',
      '#title' => $this->t('Offer recurring donation?'),
      '#markup' => $this->t('In order to make a use of recurring payments, API credentials must be provided. You can set them under your profile setting page on PayPal.'),
      '#states' => [
        'visible' => [
          ':input[name="recurring"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['recurring_container']['api_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Username:'),
      '#default_value' => $config->get('api_username'),
      '#description' => $this->t('Username of the user which is making API requests'),
      '#states' => [
        'required' => [
          ':input[name="recurring"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['recurring_container']['api_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Password:'),
      '#default_value' => $config->get('api_password'),
      '#description' => $this->t('Password of the user which is making API requests'),
      '#states' => [
        'required' => [
          ':input[name="recurring"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['recurring_container']['api_signature'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Signature:'),
      '#default_value' => $config->get('api_signature'),
      '#description' => $this->t('Signature of the user which is making API requests'),
      '#states' => [
        'required' => [
          ':input[name="recurring"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $recurring_options_values = [
      'Day' => $this->t('Daily'),
      'Week' => $this->t('Weekly'),
      'SemiMonth' => $this->t('Semi Monthly'),
      'Month' => $this->t('Monthly'),
      'Year' => $this->t('Anually'),
    ];
    $config->set('recurring_options_values', $recurring_options_values)->save();
    $form['recurring_container']['recurring_options'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Recurring options:'),
      '#default_value' => $config->get('recurring_options'),
      '#description' => $this->t('Defines in which currency donation should be made. For Semi Monthly, billing is done on the 1st and 15th of each month.'),
      '#options' => $recurring_options_values,
      '#states' => [
        'required' => [
          ':input[name="recurring"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['recurring_container']['billing_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Payment description'),
      '#default_value' => $config->get('billing_description'),
      '#states' => [
        'required' => [
          ':input[name="recurring"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['cn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Special instructions:'),
      '#default_value' => $config->get('cn'),
      '#description' => $this->t('Add special instructions to the seller.'),
      '#required' => FALSE,
    ];
    $form['no_shipping'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Do you need your customer's shipping address?"),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();
    if (!$values['allow_custom_amount'] && !$values['amounts']) {
      $form_state->setErrorByName('amounts', $this->t('Please write amounts for donating or check to allow user to write custom amount in.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('paypal_donation.settings');
    $config->set('welcome_text', $form_state->getValue('welcome_text')['value']);
    $config->set('success_text', $form_state->getValue('success_text')['value']);
    $config->set('fail_text', $form_state->getValue('fail_text')['value']);
    $config->set('sandbox', $form_state->getValue('sandbox'));
    $config->set('lc', $form_state->getValue('lc'));
    $config->set('business', $form_state->getValue('business'));
    $config->set('item_name', $form_state->getValue('item_name'));
    $config->set('item_number', $form_state->getValue('item_number'));
    $config->set('amounts', $str = preg_replace('/[^0-9,]/', '', $form_state->getValue('amounts')));
    $config->set('currency_code', $form_state->getValue('currency_code'));
    $config->set('allow_custom_amount', $form_state->getValue('allow_custom_amount'));
    $config->set('cn', $form_state->getValue('cn'));
    $config->set('no_shipping', $form_state->getValue('no_shipping'));
    $config->set('recurring', $form_state->getValue('recurring'));
    $config->set('api_username', $form_state->getValue('api_username'));
    $config->set('api_password', $form_state->getValue('api_password'));
    $config->set('api_signature', $form_state->getValue('api_signature'));
    $config->set('recurring_options', $form_state->getValue('recurring_options'));
    $config->set('billing_description', $form_state->getValue('billing_description'));

    $config->save();
    return parent::submitForm($form, $form_state);
  }

}
