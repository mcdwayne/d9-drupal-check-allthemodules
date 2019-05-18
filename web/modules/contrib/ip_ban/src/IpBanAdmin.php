<?php
namespace Drupal\ip_ban;

class IpBanAdmin extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ip_ban_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ip_ban.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ip_ban.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form = [];
    $form['#attached']['js'] = [
      drupal_get_path('module', 'ip_ban') . '/ip_ban.js'
      ];
    // Add a second submit button.
    $form['top_submit_button'] = [
      '#type' => 'submit',
      '#value' => t('Save configuration'),
    ];
    $form['ip_ban_readonly'] = [
      '#type' => 'textfield',
      '#title' => t('Read Only Message'),
      '#default_value' => variable_get('ip_ban_readonly', t('You may not create an account, attempt to log in, or request a password change from your current location.')),
      '#description' => t('The message that a user from a country set to "Read Only" will see when they attempt to access any /user/* page on this website. This message will be shown and highlighted as an error.'),
      '#size' => 100,
      '#maxlength' => 256,
    ];
    $form['ip_ban_readonly_path'] = [
      '#type' => 'textfield',
      '#title' => t('Page to redirect to if user attempts to access any user/* page based on read-only access'),
      '#default_value' => variable_get('ip_ban_readonly_path', '<front>'),
      '#description' => t('Enter a valid internal path, such as "node/1" or "content/read-only". If no path is provided, the user will be redirected to the home page.'),
      '#size' => 100,
      '#maxlength' => 256,
    ];
    $form['ip_ban_completeban'] = [
      '#type' => 'textfield',
      '#title' => t('Complete Ban Message'),
      '#default_value' => variable_get('ip_ban_completeban', 'You may not view this site from your current location.'),
      '#description' => t('The message that a user from a country set to "Complete Ban" will see when they try to access any page on this website (except the access denied or redirect page. This message will be shown and highlighted as an error.'),
      '#size' => 100,
      '#maxlength' => 256,
    ];
    $form['ip_ban_completeban_path'] = [
      '#type' => 'textfield',
      '#title' => t('Page to redirect to if user attempts to access any webpage based on "Complete Ban" access'),
      '#default_value' => variable_get('ip_ban_completeban_path', ''),
      '#description' => t('Enter a valid internal path, such as "node/1" or "content/banned". If no path is provided, the user will be redirected to access denied page (if set). If no path is set here, and no path is set for access denied, the user will only see an error message on every page.'),
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
      '#default_value' => variable_get('ip_ban_setdefault', IP_BAN_NOBAN),
      '#description' => t('Apply this setting once before you override individual countries below. Applying this setting will take a few moments to complete.'),
      '#options' => $options,
    ];

    // This is a dummy element used to iterate through its children when
    // the country options table is themed.
    $form['ip_ban_table'] = [
      '#theme' => 'ip_ban_country_table',
      '#type' => 'fieldset',
      '#title' => t('Country Listing Table'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    // Add each country selector as a child of the dummy element.
    // @see locale.inc
    $countries = country_get_list();
    foreach ($countries as $country_code => $country_name) {
      $form_name = 'ip_ban_' . $country_code;
      $form['ip_ban_table'][$form_name] = [
        '#type' => 'select',
        '#title' => t($country_name),
        '#options' => $options,
        '#default_value' => variable_get($form_name, IP_BAN_NOBAN),
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
      '#default_value' => variable_get('ip_ban_additional_ips', ''),
      '#description' => t('Add one IPV4 address per line. Example:<br/>127.0.0.1<br/>156.228.60.110'),
    ];
    $form['ip_ban_readonly_ips'] = [
      '#type' => 'textarea',
      '#title' => t('Enter additional individual IP addresses to allow read-only access'),
      '#default_value' => variable_get('ip_ban_readonly_ips', ''),
      '#description' => t('Add one IPV4 address per line. Example:<br/>127.0.0.1<br/>156.228.60.110'),
    ];
    $form['ip_ban_disabled_blocks'] = [
      '#type' => 'textarea',
      '#title' => t('Enter blocks to disable for users in "read only" mode'),
      '#default_value' => variable_get('ip_ban_disabled_blocks', 'user,login'),
      '#description' => t('<p>Add one module name (that implements the block) and delta per line, separated by a comma. If you are unsure of the module name or delta, navigate to the block configuration page. The module name will be the third to last part of the URI, and the delta will be the second to last. For example, for /admin/structure/block/manage/user/login/configure, enter "user,login" without the quotes. For a custom block like /admin/structure/block/manage/block/11/configure, enter "block,11" without the quotes.</p><p><strong>Note</strong>: there is no validation to determine if the blocks entered are enabled for any enabled theme or the admin theme.</p>'),
      '#element_validate' => [
        'ip_ban_disabled_blocks_validate'
        ],
    ];
    $form['ip_ban_test_ip'] = [
      '#type' => 'textfield',
      '#title' => t('Test IP address'),
      '#default_value' => variable_get('ip_ban_test_ip', ''),
      '#description' => t('Enter one valid IPV4 address to test your settings. Example: 156.228.60.110'),
    ];
    $form = parent::buildForm($form, $form_state);
    $form['#validate'][] = 'ip_ban_validate';
    return $form;
  }

}
