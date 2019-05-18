<?php

namespace Drupal\dea_blocker\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\dea_blocker\Classes\EmailBlacklist;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Configure module.
 */
class AdminForm extends ConfigFormBase {

  const DEFAULT_URL = 'https://github.com/ivolo/disposable-email-domains/raw/master/index.json';


  /**
   * @var Config
   */
  protected $settings;

  /**
   * @var Drupal\user\PrivateTempStore
   */
  protected $tempStore;


  /**
   * @var EmailBlacklist
   */
  protected $blacklist;



  public function __construct(ConfigFactoryInterface $config_factory, \Drupal\user\PrivateTempStoreFactory $tempStoreFactory, EmailBlacklist $blacklist) {
    parent::__construct($config_factory);
    $this->settings = $this->config('dea_blocker.settings');
    $this->tempStore = $tempStoreFactory->get('dea_blocker.admin');
    $this->blacklist = $blacklist;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('user.private_tempstore'),
      $container->get('dea_blocker.emailblacklist')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dea_blocker_admin';
  }


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [ 'dea_blocker.settings' ];
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Save new default tab.
    if (array_key_exists('settings__active_tab', $form_state->getUserInput())) {
      $this->tempStore->set('active_tab', $form_state->getUserInput()['settings__active_tab']);
    }

    // Title & styles.
    $form['#title'] = t('DEA blocker settings');

    // Main tab container.
    $form['settings'] = [
      '#type' => 'vertical_tabs',
      '#default_tab' => $this->tempStore->get('active_tab'),
    ];

    $form['blacklists'] = [
      '#type' => 'details',
      '#title' => 'Blacklist',
      '#group' => 'settings',
    ] + $this->buildBlacklistTab($form, $form_state);

    $form['forms'] = [
      '#type' => 'details',
      '#title' => 'Forms',
      '#group' => 'settings',
    ] + $this->buildFormsTab($form, $form_state);

    $form['import_export'] = [
      '#type' => 'details',
      '#title' => 'Import / Export',
      '#group' => 'settings',
    ] + $this->buildImportExportTab($form, $form_state);

    $form['test'] = [
      '#type' => 'details',
      '#title' => 'Test email',
      '#group' => 'settings',
    ] + $this->buildTestTab($form, $form_state);

    return parent::buildForm($form, $form_state);

  }


  /**
   * Build and return "Blacklist" configuration tab.
   *
   * @return array
   *   Renderable form array.
   */
  protected function buildBlacklistTab(array $form, FormStateInterface $form_state) {

    $blacklist = $this->blacklist->getItems();
    $elem['blacklist'] = [
      '#type' => 'textarea',
      '#title' => t('Domain blacklist'),
      '#description' => t(
        'Insert blacklisted mail domains, one per line (list is automatically deduplicated and sorted).<br/>'
        .'You can also use regular expressions by wrapping it with <b>/</b> char like @sample.<br/>'
        .'<b>EXAMPLE</b>: @regex will blacklist both @email1 and @email2.', [
        '@regex' => Markup::create('<code>/example\.org$/</code>'),
        '@email1' => Markup::create('<code>foo@example.org</code>'),
        '@email2' => Markup::create('<code>bar@subdomain.example.org</code>'),
        '@sample' => Markup::create('<code>/^example\.(com|org)$/</code>'),
      ]),
      '#default_value' => $blacklist ? implode("\r\n", $blacklist) ."\r\n" : '',
      '#rows' => 15,
    ];

    return $elem;

  }


  /**
   * Build and return "Forms" configuration tab.
   *
   * @return array
   *   Renderable form array.
   */
  protected function buildFormsTab(array $form, FormStateInterface $form_state) {

    $forms = $this->settings->get('forms') ?: [];

    $options = [
      'all'       => t('all forms'),
      '!sel' => t('all forms except the ones listed below'),
      'sel'  => t('only the forms listed below'),
      'off'       => t('none (off)'),
    ];
    $elem['mode'] = [
      '#type' => 'radios',
      '#title' => t('Protect email fields on...'),
      '#options' => $options,
      '#default_value' => $this->settings->get('mode') ?: 'off',
    ];

    $elem['forms'] = [
      '#type' => 'textarea',
      '#description' => t('List of form IDs, one per line.'),
      '#default_value' => $forms
                          ? implode("\r\n", $forms) . "\r\n"
                          : '',
      '#states' => [
        'enabled' => [
          [':input[name="mode"]' => ['value' => 'sel']],
          'or',
          [':input[name="mode"]' => ['value' => '!sel']],
        ],
      ],
    ];

    $elem['show_form_selector'] = [
      '#type' => 'checkbox',
      '#title' => t('Append additional block under each form to ease form ID selection'),
      '#description' => t('NOTE: block will be shown only to users with "Administer DEA blocker" permission.'),
      '#default_value' => $this->settings->get('show_form_selector'),
    ];

    return $elem;

  }


  /**
   * Build and return "ImportExport" configuration tab.
   *
   * @return array
   *   Renderable form array.
   */
  protected function buildImportExportTab(array $form, FormStateInterface $form_state) {

    $elem['blacklist_export'] = [
      '#type' => 'fieldset',
      '#title' => t('Export'),
      'label' => [
        '#type' => 'item',
        '#description' => 'Export blacklist to JSON file. The downloaded file can be edited then imported back.',
      ],
      'blacklist_export_link' => [
        '#type' => 'link',
        '#title' => t('Export'),
        '#url' => Url::fromRoute('dea_blocker.json_export'),
        '#attributes' => ['class' => ['button']],
      ],
    ];

    $elem['blacklist_import'] = [
      '#type' => 'fieldset',
      '#title' => t('Import'),
      'label' => [
        '#type' => 'item',
        '#description' => 'Import (upload) a JSON formatted file containing domain blacklist. Its content will be <strong>merged</strong> with current blacklist.',
      ],
      'file_container' => [
        '#type' => 'container',
        '#attributes' => array('class' => array('container-inline')),
        'blacklist_import_upload_file' => [
          '#type' => 'file',
        ],
        'cmd_import_upload' => [
          '#type' => 'submit',
          '#value'=> t('Import'),
          '#validate' => ['::validateImportUpload'],
          '#submit' => ['::submitImportUpload'],
        ],
      ],
    ];

    $last_import = \Drupal::state()->get('dea_blocker.last_import', 0);
    $elem['blacklist_cron_import'] = [
      '#type' => 'fieldset',
      '#title' => t('Automatic import'),
      'label' => [
        '#type' => 'item',
        '#description' => 'Automatically download a JSON file containing domain blacklist and <strong>merge</strong> its content with current blacklist.',
      ],
      'import_url' => [
        '#type' => 'textfield',
        '#title' => t('URL of JSON file to import'),
        '#default_value' => $this->settings->get('import_url') ?: self::DEFAULT_URL,
        '#size' => 100,
        '#description' => t(
          'A good example can be found in <a href=":url">:repo</a> GitHub repository.<br/>Its JSON file URL is <strong>:json</strong>', [
            ':repo' => 'ivolo/disposable-email-domains',
            ':url' => 'https://github.com/ivolo/disposable-email-domains',
            ':json' => self::DEFAULT_URL,
          ]
        ),
      ],
      'import_frequency' => [
        '#type' => 'select',
        '#title' => t('Update frequency'),
        '#default_value' => $this->settings->get('import_frequency') ?: 0,
        '#options' => [
          '0'  => t('disabled'),
          '1'  => t('1 day'),
          '3'  => t(':number days', [':number' => 3]),
          '7'  => t(':number days', [':number' => 7]),
          '14' => t(':number days', [':number' => 14]),
        ],
      ],
      'last_import' => [
        '#type' => 'item',
        '#title' => t('Last import'),
        '#markup' => $last_import ? format_date($last_import, 'short') : t('never'),
      ],
      'cmd_import_url' => [
        '#type' => 'submit',
        '#value' => t('Import now'),
        '#validate' => ['::validateImportUrl'],
        '#submit' => ['::submitImportUrl'],
      ],
    ];

    return $elem;

  }


  /**
   * Build and return "Test" configuration tab.
   *
   * @return array
   *   Renderable form array.
   */
  protected function buildTestTab(array $form, FormStateInterface $form_state) {

    $elem['test_email'] = [
      '#type' => 'email',
      '#title' => t('Test email'),
      '#description' => t('Test this email address against configured blacklists'),
      '#default_value' => $this->tempStore->get('test_email'),
    ];
    $elem['actions'] = [
      '#type' => 'actions',
      '#value' => t('Test'),
    ];
    $elem['actions']['cmd_test'] = [
      '#type' => 'submit',
      '#value' => t('Test'),
      '#submit' => ['::submitTest'],
    ];
    return $elem;

  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Save blacklist.
    $blacklist = $this->stringListToArray($form_state->getValue('blacklist', ''));
    $errors = EmailBlacklist::validateItems($blacklist);
    if ($errors) {
      $error_msg = t('One or more regular expression patterns are not valid:');
      foreach ($errors as $value => $error) {
        $error_msg .= '<br/><b>'.$value.'</b> - '.$error;
      }
      $form_state->setErrorByName('blacklist', Markup::create($error_msg));
    }
    else {
      $form_state->setValue('blacklist', $blacklist);
    }

    // Form IDs.
    $form_state->setValue('forms', $this->stringListToArray($form_state->getValue('forms', '')));

    parent::validateForm($form, $form_state);

  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Save blacklist.
    $this->blacklist->clear();
    $this->blacklist->addItems($form_state->getValue('blacklist'));
    $this->blacklist->save();

    // Save field values.
    $fields = [
      'show_form_selector',
      'mode',
      'forms',
      'import_url',
      'import_frequency',
    ];
    foreach ($fields as $field) {
      $this->settings->set($field, $form_state->getValue($field));
    }
    $this->settings->save();

    parent::submitForm($form, $form_state);

  }


  /**
   * Validation for import a JSON file from external URL.
   */
  public function validateImportUrl(array &$form, FormStateInterface $form_state) {

    if (!$form_state->getValue('import_url')) {
      $form_state->setErrorByName('import_url', t('Invalid URL'));
      return;
    }

    // Test if config is updated.
    $saved_url = $this->settings->get('import_url', '');
    if ($saved_url != $form_state->getValue('import_url')) {
      $form_state->setErrorByName('import_url', t('Need to save configuration first'));
      return;
    }

  }


  /**
   * Download and import a JSON file from external URL.
   */
  public function submitImportUrl(array &$form, FormStateInterface $form_state) {

    $url = $this->settings->get('import_url');
    if (!_dea_blocker_import_from_url($url, $result)) {
      drupal_set_message(Markup::create($result), 'error');
    }
    else {
      // Import OK.
      drupal_set_message(Markup::create($result));
      // Update last execution.
      \Drupal::state()->set('dea_blocker.last_import', time());
    }

  }


  /**
   * Validation for uploaded JSON file.
   */
  public function validateImportUpload(array &$form, FormStateInterface $form_state) {

    // Get temporary uploaded filename.
    $tmpFilename = isset($_FILES['files']['tmp_name']['blacklist_import_upload_file'])
                  ? $_FILES['files']['tmp_name']['blacklist_import_upload_file']
                  : '';
    if (!$tmpFilename) {
      $form_state->setErrorByName('blacklist_import_upload_file', t('Please upload a JSON file'));
      return;
    }

  }


  /**
   * Import an uploaded JSON file.
   */
  public function submitImportUpload(array &$form, FormStateInterface $form_state) {

    // Get temporary uploaded filename.
    $tmpFilename = $_FILES['files']['tmp_name']['blacklist_import_upload_file'];
    $json_content = file_get_contents($tmpFilename);
    if (_dea_blocker_import_from_json($json_content, $result)) {
      drupal_set_message($result);
    }
    else {
      drupal_set_message($result, 'error');
    }

    // Remove temporary file.
    file_unmanaged_delete($tmpFilename);

  }


  /**
   * Test the email against blacklist.
   */
  public function submitTest(array &$form, FormStateInterface $form_state) {

    $email = $form_state->getValue('test_email', '');
    $this->tempStore->set('test_email', $email);

    if ($email) {
      // Blacklist service instance.
      /* @var $blacklist EmailBlacklist */
      $blacklist = \Drupal::getContainer()->get('dea_blocker.emailblacklist');

      if ($blacklist->isBlacklisted($email)) {
        drupal_set_message(t('Email address :email is blacklisted', [':email' => $email]), 'error');
      }
      else {
        drupal_set_message(t('Email address :email is not blacklisted', [':email' => $email]));
      }
    }
    else {
      drupal_set_message(t('Please insert an email value to test.'), 'error');
    }

  }


  /**
   * Transform the given items list (string), separated with \r\n, into an array:
   * - converted to lowercase
   * - empty lines removed
   * - duplicate lines removed
   * - sorted
   *
   * @param string $value
   *   Items string, separated by \r\n.
   *
   * @return array
   */
  protected function stringListToArray(string $value) {
    return $this->cleanupArray(explode("\r\n", strtolower($value)));
  }


  /**
   * Cleanup the given array :
   * - empty items removed
   * - duplicate items removed
   * - sorted
   *
   * @param array $value
   *   Array to cleanup.
   *
   * @return array
   */
  protected function cleanupArray(array $value) {
    $array = array_filter(array_unique($value));
    sort($array);
    return $array;
  }


  /**
   * Add or remove a FormID to/from the forms list.
   * This method is called in a controller-like way.
   *
   * @param string $command
   *   Command to execute, can be 'add' or 'remove'.
   * @param string $formId
   *   ID of the form to add/remove.
   */
  public function addRemoveForm($command, $formId) {

    if (!$formId) return;

    $forms = $this->settings->get('forms');
    switch ($command) {
      case 'add_form':
        $forms[] = $formId;
        drupal_set_message(t('Form :formId added to list', [':formId' => $formId]));
        break;
      case 'remove_form':
        if (($key = array_search($formId, $forms)) !== FALSE) {
          unset($forms[$key]);
        }
        drupal_set_message(t('Form :formId removed from list', [':formId' => $formId]));
        break;
    }
    $this->settings
      ->set('forms', $this->cleanupArray($forms))
      ->save();

    // Set the default tab.
    $this->tempStore->set('active_tab', 'edit-forms');

    // Returns a RedirectResponse.
    return new RedirectResponse(Url::fromRoute('dea_blocker.admin')->toString());

  }


  /**
   * Returns a JSON file with current configuration.
   */
  public function exportJson() {
    $blacklist = $this->settings->get('blacklist') ?: [];
    $response = new \Symfony\Component\HttpFoundation\JsonResponse();
    $response->setContent(json_encode($blacklist));
    $response->headers->set('Content-Disposition', 'attachment');
    return $response;
  }

}
