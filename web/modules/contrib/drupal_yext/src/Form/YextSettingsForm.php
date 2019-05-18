<?php

namespace Drupal\drupal_yext\Form;

use Drupal\drupal_yext\traits\CommonUtilities;
use Drupal\drupal_yext\Yext\Yext;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * The settings form for Yext.
 */
class YextSettingsForm extends FormBase {

  use CommonUtilities;

  /**
   * Ajax callback to factory-reset the Yext import system.
   */
  public function ajaxResetAll(array $form, FormStateInterface $form_state) {
    $this->yext()->resetAll();
    return $this->ajaxYextUpdateImportData($form, $form_state);
  }

  /**
   * Ajax callback to import some more Yext items.
   *
   * Note that this could timeout relatively easily.
   */
  public function ajaxYextImportSome(array $form, FormStateInterface $form_state) {
    $this->yext()->importSome();
    return $this->ajaxYextUpdateImportData($form, $form_state);
  }

  /**
   * Ajax callback to test Yext.
   */
  public function ajaxYextTest(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new HtmlCommand('#check-icon-wrapper', $this->yextTestIcon($input['DrupalYext_yextapi'], $input['DrupalYext_yextaccount'], $input['DrupalYext_yextbase'])));
    $ajax_response->addCommand(new HtmlCommand('#ajax-yext-test', $this->yextTestString($input['DrupalYext_yextapi'], $input['DrupalYext_yextaccount'], $input['DrupalYext_yextbase'])));
    $ajax_response->addCommand(new HtmlCommand('#ajax-yext-more', $this->yextTestStringMore($input['DrupalYext_yextapi'], $input['DrupalYext_yextaccount'], $input['DrupalYext_yextbase'])));
    return $ajax_response;
  }

  /**
   * Ajax callback to update via Ajax the Yext data presented on the form.
   */
  public function ajaxYextUpdateImportData(array $form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();

    $imported = $this->yext()->imported();
    $failed = $this->yext()->failed();
    $next_date = $this->yext()->nextDateToImport('Y-m-d');
    $last_check = $this->yext()->lastCheck('Y-m-d H:i:s');
    $remaining = $this->yext()->remaining();

    $ajax_response->addCommand(new HtmlCommand('#yext-imported', $imported));
    $ajax_response->addCommand(new HtmlCommand('#yext-failed', $failed));
    $ajax_response->addCommand(new HtmlCommand('#yext-next-date', $next_date));
    $ajax_response->addCommand(new HtmlCommand('#yext-last-check', $last_check));
    $ajax_response->addCommand(new HtmlCommand('#yext-remaining', $remaining));

    return $ajax_response;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'DrupalYext_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    foreach ($this->fieldmap()->errors() as $error) {
      $this->drupalSetMessage($error['text'], 'error');
    }
    $form = [];
    $form['#attached']['library'][] = 'drupal_yext/ajaxy';
    $form['yextbase'] = [
      '#type' => 'details',
      '#title' => $this->t('Basic node information'),
      '#description' => $this->t('This website attempts to synchronize data from @yext using their @api, creating nodes. Enter information about the target nodes here.', [
        '@yext' => $this->link('Yext', 'https://www.yext.com')->toString(),
        '@api' => $this->link('API', 'http://developer.yext.ca/docs/guides/get-started/')->toString(),
      ]),
      '#open' => FALSE,
    ];
    $form['yextbase']['DrupalYextBase.nodetype'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('The target node type'),
      '#description' => $this->t('Something like "article" or "doctor". This is not validated, so please make sure it exists.'),
      '#default_value' => $this->yext()->yextNodeType(),
    );
    $form['yextbase']['DrupalYextBase.uniqueidfield'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('The target ID field'),
      '#description' => $this->t('Something like "field_yext_id". This is not validated, so please make sure it exists.'),
      '#default_value' => $this->yext()->uniqueYextIdFieldName(),
    );
    $form['yextbase']['DrupalYextBase.uniquelastupdatedfield'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('The target "last updated" field'),
      '#description' => $this->t('Something like "field_yext_last_updated". This is not validated, so please make sure it exists.'),
      '#default_value' => $this->yext()->uniqueYextLastUpdatedFieldName(),
    );
    try {
      $form['yext'] = array(
        '#type' => 'details',
        '#title' => $this->t('Yext integration'),
        '#description' => $this->t('This website attempts to synchronize data from @yext using their @api, creating nodes. Once you have an "app" and API key set up, you can enter them here.', [
          '@yext' => $this->link('Yext', 'https://www.yext.com')->toString(),
          '@api' => $this->link('API', 'http://developer.yext.ca/docs/guides/get-started/')->toString(),
        ]),
        '#open' => FALSE,
      );
      $base = $this->yext()->base();
      $form['yext']['DrupalYext.yextbase'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Yext base URL'),
        '#description' => $this->t('Something like @b. This is a state variable, not config, so it can differ between environments. Be careful to use the appropriate base for your needs: https://liveapi.yext.com and https://api.yext.com work differently, and this module has been tested especially with https://liveapi.yext.com.', [
          '@b' => $this->yext()->defaultBase(),
        ]),
        '#default_value' => $base,
      );
      $form['yext']['DrupalYext.filters'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Extra get parameters'),
        '#description' => $this->t('One per line as per <a href="https://developer.yext.ca/docs/live-api" target="_blank">the API documenentaion</a>. One example of what to put here would be <code>[{"locationType":{"is":[2]}}]</code>. Every line should be in exactly that format. This is configuration, not a state variable, so you can management via config management between environments.'),
        '#default_value' => $this->yext()->filtersAsText(),
      );
      $form['yext']['DrupalYext.yextnext'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Next check date for Yext'),
        '#description' => $this->t('YYYY-MM-DD; this is set automatically during normal operation.'),
        '#default_value' => $this->yext()->nextDateToImport('Y-m-d'),
      );
      $acct = $this->yext()->accountNumber();
      $form['yext']['DrupalYext.yextaccount'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Yext account number'),
        '#description' => $this->t('This is something like 123456 or, if you have only one account, "me". This is a state variable, not config, so it can differ between environments.'),
        '#default_value' => $acct,
      );
      $key = $this->yext()->apiKey();
      $form['yext']['DrupalYext.yextapi'] = array(
        '#type' => 'password',
        '#title' => $this->t('Yext API key'),
        '#description' => '<strong>' . $this->t('For security reasons, you will not be able to see the API key even if it is entered.') . '</strong> ' . $this->t('Can be found in your "app" in the Yext developer console.') . $this->t('This is a state variable, not config, so it can differ between environments.'),
        '#default_value' => $key,
      );
      $checkmessage = $this->yextTestString();
      $checkmessagemore = $this->yextTestStringMore();
      $icon = $this->yextTestIcon();
      $form['yext']['DrupalYext.ajaxYextTest'] = array(
        '#type' => 'button',
        '#value' => $this->t('Test the API key'),
        '#description' => $this->t('Attempts to connect to the Yext API.'),
        '#ajax' => array(
          'callback' => '::ajaxYextTest',
          'effect' => 'fade',
          'event' => 'click',
          'progress' => array(
            'type' => 'throbber',
            'message' => NULL,
          ),
        ),
        '#prefix' => <<< HEREDOC
<span class="system-status-counter system-status-counter--error">
  <span class="yext-ajaxy" id="check-icon-wrapper">$icon</span>
  <span class="system-status-counter__status-title">
    <span class="system-status-counter__title-count"><span class="yext-ajaxy" id="ajax-yext-test">$checkmessage</span>&nbsp;<span>
HEREDOC
        ,
        '#suffix' => '</span></span></span></span></span><div id="ajax-yext-more">' . $checkmessagemore . '</div>',
      );
    }
    catch (\Throwable $t) {
      $this->watchdogThrowable($t);
    }
    try {
      $form['yextimport'] = array(
        '#type' => 'details',
        '#title' => $this->t('Yext import status'),
        '#description' => $this->t('Assuming Yext integration works, this is where we are at in the import (imports are performed on cron runs).') . ' ' . $this->t('Imports some more data. Be careful using the Import more button because this can timeout with large amounts of data. It is recommended to use "drush ev \'drupal_yext_import_some()\'" on the command line for more heavy-duty imports; the very first time you start importing content, you might to call that several times, for example: for i in `seq 1 100`; do drush ev "drupal_yext_import_some()"; echo $i; done.') . ' ' . $this->t('Resets the API importer to its initial state. Useful for testing.'),
        '#open' => FALSE,
      );
      $imported = $this->yext()->imported();
      $failed = $this->yext()->failed();
      $next_date = $this->yext()->nextDateToImport('Y-m-d');
      $last_check = $this->yext()->lastCheck('Y-m-d H:i:s');
      $remaining = $this->yext()->remaining();
      $form['yextimport']['DrupalYext.ajaxResetAll'] = array(
        '#type' => 'button',
        '#value' => $this->t('Reset the API importer'),
        '#ajax' => array(
          'callback' => '::ajaxResetAll',
          'effect' => 'fade',
          'event' => 'click',
          'progress' => array(
            'type' => 'throbber',
            'message' => NULL,
          ),
        ),
      );
      $form['yextimport']['details'] = array(
        '#type' => 'markup',
        '#markup' => '<ul>
          <li><span class="yext-ajaxy" id="yext-imported">' . $imported . '</span> nodes imported.</li>
          <li><span class="yext-ajaxy" id="yext-failed">' . $failed . '</span> nodes failed to import.</li>
          <li>We have updated nodes updated on or before <span class="yext-ajaxy" id="yext-next-date">' . $next_date . '<span>.</li>
          <li>Last check: <span class="yext-ajaxy" id="yext-last-check">' . $last_check . '</span>.</li>
          <li><span class="yext-ajaxy" id="yext-remaining">' . $remaining . '</span> nodes remaining.</li>
        <ul>',
      );
      $form['yextimport']['DrupalYext.ajaxImportSome'] = array(
        '#type' => 'button',
        '#value' => $this->t('Import more'),
        '#ajax' => array(
          'callback' => '::ajaxYextImportSome',
          'effect' => 'fade',
          'event' => 'click',
          'progress' => array(
            'type' => 'throbber',
            'message' => NULL,
          ),
        ),
      );
    }
    catch (\Throwable $t) {
      $this->watchdogThrowable($t);
    }
    $this->formAddMappingSection($form);
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * Helper function to add a "mapping" section to the form.
   *
   * @param array $form
   *   The form object.
   */
  public function formAddMappingSection(array &$form) {
    $form['fieldmapping'] = array(
      '#type' => 'details',
      '#title' => $this->t('Yext field mapping'),
      '#description' => $this->t('Map Yext API fields to Drupal fields.'),
      '#open' => FALSE,
    );
    $form['fieldmapping']['headshot'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Headshot image field'),
      '#description' => $this->t('The headshot image field which should exist for node type mapped to Yext, for example field_yext_headshot.'),
      '#default_value' => $this->fieldmap()->headshot(),
    );
    $form['fieldmapping']['bio'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Bio formatted text field'),
      '#description' => $this->t('The bio formatted text field which should exist for node type mapped to Yext, for example "body".'),
      '#default_value' => $this->fieldmap()->bio(),
    );
    $form['fieldmapping']['geo'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Geofield field'),
      '#description' => $this->t('If you are using the geofield module (and its geoPHP dependency as explained on the project homepage) and have a geofield field (for example field_geofield) in your content type, you can populate it from Yext.'),
      '#default_value' => $this->fieldmap()->geo(),
    );
    $form['fieldmapping']['raw'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Raw long plain text field'),
      '#description' => $this->t('The raw long plain text field which should exist for node type mapped to Yext, for example field_yext_raw.'),
      '#default_value' => $this->fieldmap()->raw(),
    );
    $form['fieldmapping']['yextfieldmapping'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Yext field mapping'),
      '#description' => $this->t('Enter Yext field mapping which you get from your Yext account manager or from the API using the technique outlined at http://developer.yext.com/docs/api-reference/#operation/getCustomFields. Please use the format <code>"1234","field_drupal_field","because 1234 is numeric we will look for it in the customFields section of the json object."</code><br/><code>"2345",,"this is not mapped to drupal but exists in yext"</code><br/><code>"address","field_address","because address is non-numeric, we will look for it in the root of the json object."</code><br/><code>"closed][isClosed","field_closed","The ][ notation denotes we want the value of the isClosed key within the closed key, itself being an array."</code>'),
      '#default_value' => $this->fieldmap()->fieldMapping(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $this->yext()->setNodeType($input['DrupalYextBase_nodetype']);
    $this->yext()->setUniqueYextIdFieldName($input['DrupalYextBase_uniqueidfield']);
    $this->fieldmap()->setFieldMapping($input['yextfieldmapping']);
    $this->fieldmap()->setRaw($input['raw']);
    $this->fieldmap()->setGeo($input['geo']);
    $this->fieldmap()->setBio($input['bio']);
    $this->fieldmap()->setHeadshot($input['headshot']);
    $this->yext()->setUniqueYextLastUpdatedFieldName($input['DrupalYextBase_uniquelastupdatedfield']);
    $this->yext()->accountNumber($input['DrupalYext_yextaccount']);
    $this->yext()->apiKey($input['DrupalYext_yextapi']);
    $this->yext()->base($input['DrupalYext_yextbase']);
    $this->yext()->filtersAsText($input['DrupalYext_filters']);
    $this->yext()->setNextDate($input['DrupalYext_yextnext']);
    $this->drupalSetMessage($this->t('Settings saved successfully.'));
  }

  /**
   * Get the Yext singleton.
   *
   * @return Yext
   *   The Yext singleton.
   *
   * @throws Exception
   */
  public function yext() : Yext {
    return Yext::instance()->yext();
  }

  /**
   * Get a Yext icon to use for the test result.
   *
   * @param string $key
   *   An API key to use.
   * @param string $account
   *   An account to use.
   * @param string $base
   *   A Yext base URL.
   *
   * @return string
   *   HTML markup for the icon.
   */
  public function yextTestIcon(string $key = '', string $account = '', string $base = '') : string {
    $yext_test = $this->yext()->test($key, $account, $base);
    if (!empty($yext_test['success'])) {
      $class = 'checked';
    }
    else {
      $class = 'error';
    }
    return '<span class="system-status-counter__status-icon system-status-counter__status-icon--' . $class . '"></span>';
  }

  /**
   * Return the string describing the result of a Yext test.
   *
   * @param string $key
   *   An API key to use.
   * @param string $account
   *   An account to use.
   * @param string $base
   *   A Yext base URL.
   *
   * @return string
   *   A string.
   */
  public function yextTestString(string $key = '', string $account = '', string $base = '') : string {
    $yext_test = $this->yext()->test($key, $account, $base);
    if (isset($yext_test['message']) && is_string($yext_test['message'])) {
      return $yext_test['message'];
    }
    else {
      return 'Please make sure Yext::test() returns an array with a message key, not ' . serialize($yext_test);
    }
  }

  /**
   * Return more information about the Yext server test.
   *
   * @param string $key
   *   An API key to use.
   * @param string $account
   *   An account to use.
   * @param string $base
   *   A Yext base URL.
   *
   * @return string
   *   A string.
   */
  public function yextTestStringMore(string $key = '', string $account = '', string $base = '') : string {
    $yext_test = $this->yext()->test($key, $account, $base);
    if (isset($yext_test['more']) && is_string($yext_test['more'])) {
      return $yext_test['more'];
    }
    else {
      return 'Please make sure Yext::test() returns an array with a "more" key, not ' . serialize($yext_test);
    }
  }

}
