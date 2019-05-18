<?php

namespace Drupal\ip_ban\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Locale;

class IpBanAdmin extends ConfigFormBase {
  
  /**
   * The list of country short names
   *
   * @var array
   */
  protected $country_short_names;
  

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ip_ban_admin';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ip_ban.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form = [];
    $form['#attached']['library'][] = 'ip_ban/ip_ban.admin_form';
    // Add a second submit button.
    $form['top_submit_button'] = [
      '#type' => 'submit',
      '#value' => t('Save configuration'),
    ];
    // $form['ip_ban_readonly'] = [
      // '#type' => 'textfield',
      // '#title' => t('Read Only Message'),
      // '#default_value' => \Drupal::config('ip_ban.settings')->get('ip_ban_readonly'),
      // '#description' => t('The message that a user from a country set to "Read Only" will see when they attempt to access any /user/* page on this website. This message will be shown and highlighted as an error.'),
      // '#size' => 100,
      // '#maxlength' => 256,
    // ];
    $form['ip_ban_readonly_path'] = [
      '#type' => 'textfield',
      '#title' => t('Page to redirect to if user attempts to access any user/* page based on read-only access'),
      '#default_value' => \Drupal::config('ip_ban.settings')->get('ip_ban_readonly_path'),
      '#description' => t('Enter a valid internal path, such as "/node/1" or "/content/read-only".'),
      '#required' => TRUE,
      '#size' => 100,
      '#maxlength' => 256,
    ];
    $form['ip_ban_completeban'] = [
      '#type' => 'textfield',
      '#title' => t('Complete Ban Message'),
      '#default_value' => \Drupal::config('ip_ban.settings')->get('ip_ban_completeban'),
      '#description' => t('The message that a user from a country set to "Complete Ban" will see when they try to access any page on this website (except the access denied or redirect page. This message will be shown and highlighted as an error.'),
      '#size' => 100,
      '#maxlength' => 256,
    ];
    $form['ip_ban_completeban_path'] = [
      '#type' => 'textfield',
      '#title' => t('Page to redirect to if user attempts to access any webpage based on "Complete Ban" access'),
      '#default_value' => \Drupal::config('ip_ban.settings')->get('ip_ban_completeban_path'),
      '#description' => t('Enter a valid internal path, such as "/node/1" or "/content/banned". If no path is provided, the user will only see an error message on every page.'),
      '#size' => 100,
      '#maxlength' => 256,
    ];
    $options = [
      IP_BAN_NOBAN => '',
      IP_BAN_READONLY => t('Read Only'),
      IP_BAN_BANNED => t('Complete Ban'),
    ];
    $form['ip_ban_setdefault'] = [
      '#type' => 'select',
      '#title' => t('Dynamically set the default value for each country.'),
      '#default_value' => \Drupal::config('ip_ban.settings')->get('ip_ban_setdefault'),
      '#description' => t('Apply this setting once before you override individual countries below.'),
      '#options' => $options,
    ];

    // This is a dummy element used to iterate through its children when
    // the country options table is themed.
    $form['ip_ban_table'] = [
      // '#theme' => 'ip_ban_country_table',
      '#type' => 'details',
      '#title' => t('Country Listing Table'),
      '#open' => TRUE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
    ];
    // Add each country selector as a child of the dummy element.
    $countries = \Drupal::service('country_manager')->getStandardList();
    foreach ($countries as $country_code => $country_name) {
      $form_name = 'ip_ban_' . $country_code;
      $this->country_short_names[] = $form_name;
      $form['ip_ban_table'][$form_name] = [
        '#type' => 'select',
        '#title' => t($country_name->getUntranslatedString()),
        '#options' => $options,
        '#default_value' => \Drupal::config('ip_ban.settings')->get($form_name),
        '#attributes' => [
          'class' => [
            'ip-ban-table-cell'
            ]
          ],
      ];
    }
    $form['ip_ban_additional_ips'] = [
      '#type' => 'textarea',
      '#title' => t('Enter additional individual IP addresses to ban'),
      '#default_value' => \Drupal::config('ip_ban.settings')->get('ip_ban_additional_ips'),
      '#description' => t('Add one IPV4 address per line. Example:<br/>127.0.0.1<br/>156.228.60.110'),
    ];
    $form['ip_ban_readonly_ips'] = [
      '#type' => 'textarea',
      '#title' => t('Enter additional individual IP addresses to allow read-only access'),
      '#default_value' => \Drupal::config('ip_ban.settings')->get('ip_ban_readonly_ips'),
      '#description' => t('Add one IPV4 address per line. Example:<br/>127.0.0.1<br/>156.228.60.110'),
    ];
    $form['ip_ban_disabled_blocks'] = [
      '#type' => 'textarea',
      '#title' => t('Enter blocks to disable for users in "read only" mode'),
      '#default_value' => \Drupal::config('ip_ban.settings')->get('ip_ban_disabled_blocks'),
      '#description' => t('<p>Add one module name (that implements the block) and delta per line, separated by a comma. If you are unsure of the module name or delta, navigate to the block configuration page. The module name will be the third to last part of the URI, and the delta will be the second to last. For example, for /admin/structure/block/manage/user/login/configure, enter "user,login" without the quotes. For a custom block like /admin/structure/block/manage/block/11/configure, enter "block,11" without the quotes.</p><p><strong>Note</strong>: there is no validation to determine if the blocks entered are enabled for any enabled theme or the admin theme.</p>'),
      '#element_validate' => array(
        array($this, 'iPBanDisabledBlocksValidate'),
      ),
    ];
    $form['ip_ban_test_ip'] = [
      '#type' => 'textfield',
      '#title' => t('Test IP address'),
      '#default_value' => \Drupal::config('ip_ban.settings')->get('ip_ban_test_ip'),
      '#description' => t('Enter one valid IPV4 address to test your settings. Example: 156.228.60.110'),
    ];
    $form = parent::buildForm($form, $form_state);
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ip_ban.settings');
    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $values = $form_state->getValues();
    foreach ($this->country_short_names as $csn){
      $config->set($csn, $values[$csn]);
    }
    $config->save();
    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }
    // Clear the router cache because we're dealing with paths.
    \Drupal::service("router.builder")->rebuild();
    parent::submitForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->iPBanValidatePaths('ip_ban_readonly_path', \Drupal\Component\Utility\Html::escape($form_state->getValue('ip_ban_readonly_path')), $form, $form_state);
    $this->iPBanValidatePaths('ip_ban_completeban_path', \Drupal\Component\Utility\Html::escape($form_state->getValue('ip_ban_completeban_path')), $form, $form_state);
    $this->iPBanValidateIPs('ip_ban_additional_ips', \Drupal\Component\Utility\Html::escape($form_state->getValue('ip_ban_additional_ips')), $form, $form_state);
    $this->iPBanValidateIPs('ip_ban_readonly_ips', \Drupal\Component\Utility\Html::escape($form_state->getValue('ip_ban_readonly_ips')), $form, $form_state);
    $this->iPBanValidateIPs('ip_ban_test_ip', \Drupal\Component\Utility\Html::escape($form_state->getValue('ip_ban_test_ip')), $form, $form_state);
  }  
  
  /**
   * Custom validation function for path redirects.
   *
   * Custom validation function for the path to redirect to for banned or
   * read-only users. Here we simply ensure the path specified exists, and if not,
   * display a form error.
   */
  private function iPBanValidatePaths($form_element, $form_value, array &$form, FormStateInterface $form_state) {
    // An empty path is valid here because the path is not a required field.
    if (!empty($form_value)) {
      $normal_path = \Drupal::service('path.alias_manager')->getPathByAlias($form_value);
      if (!\Drupal::service('path.validator')->isValid($normal_path)) {
        $form_state->setErrorByName($form_element, $this->t('The path entered does not exist or you do not have permission to access it.'));
      }
      // Check if the first character entered is a 
      if ($normal_path[0] != "/") {
        $form_state->setErrorByName($form_element, $this->t('The path must start with a forward slash (/).'));
      }
    }
  }

  /**
   * Custom validation function for valid IP addresses.
   *
   * Custom validation function for the list of additional IP addresses to either
   * ban or mark as read-only. We convert the textarea into an array of IP
   * addresses, then check if each address is valid. If any one line is invalid,
   * we set the entire form element to invalid.
   */
  private function iPBanValidateIPs($form_element, $form_value, array &$form, FormStateInterface $form_state) {
    if (!empty($form_value)) {
      $ip_array = explode(PHP_EOL, $form_value);
      foreach ($ip_array as $ip) {
        if (filter_var(trim($ip), FILTER_VALIDATE_IP) == FALSE) {
          $form_state->setErrorByName($form_element, t('You have entered one or more incorrect IPV4 addresses.'));
        }
      }
    }
  }  
  
  /**
   * Determine if blocks entered are valid and formatted correctly.
   */
  function iPBanDisabledBlocksValidate($form, &$form_state) {
    // $disabled_blocks = \Drupal\Component\Utility\Html::escape($form_state['values']['ip_ban_disabled_blocks']);
    // if (!empty($disabled_blocks)) {
      // $disabled_block_array = explode(PHP_EOL, $disabled_blocks);
      // foreach ($disabled_block_array as $disabled_block) {
        // // First determine if the user entered two strings separated by a space.
        // $module_and_delta = explode(',', trim($disabled_block));
        // if (count($module_and_delta) != 2) {
          // form_set_error('ip_ban_disabled_blocks', t('You have one or more blocks with an incorrect format; you must enter exactly one module name and delta name per line, separated by a comma.'));
        // }
        // else {
          // $module = trim($module_and_delta[0]);
          // $delta = trim($module_and_delta[1]);
          // // Second determine if the block entered is a valid block.
          // $disabled_block = db_query('SELECT * FROM {block} WHERE module = :module AND delta = :delta', array(':module' => $module, ':delta' => $delta))->fetchAll();
          // if (empty($disabled_block)) {
            // form_set_error('ip_ban_disabled_blocks', t('You entered at least one invalid module name or delta; see the help text for how to enter the proper module name and delta.'));
          // }
          // // Todo: add check for block enabled status for enabled themes. 
          // // If block disabled for all enabled themes (including admin theme), 
          // // set form error.
        // }
      // }
    // }
  }


}
