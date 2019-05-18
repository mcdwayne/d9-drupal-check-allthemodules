<?php

namespace Drupal\httpbl\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormErrorInterface;

/**
 * Defines a form that configures httpbl settings.
 */
class HttpblConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'httpbl_admin_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'httpbl.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('httpbl.settings');
    
    // Warn user if not yet correctly configured to run checks.
    if (!$_POST && (!\Drupal::state()->get('httpbl.accesskey') ?: NULL || !\Drupal::state()->get('httpbl.check') ?: HTTPBL_CHECK_NONE )) {
    drupal_set_message(t('Project Honey Pot lookups are currently disabled; enter your access key below and enable checks to enable lookups.'), 'warning');
    }

    // Create link to HttpBL home page.
    $httpblUrl = \Drupal\Core\Url::fromUri('http://www.projecthoneypot.org/httpbl.php');
    $httpblUrl_options = [
      'attributes' => [
        'target' => '_blank',
        'title' => t('More info about Project Honey Pot\'s http:BL service.'),
      ]];
    $httpblUrl->setOptions($httpblUrl_options);
    $httpbl_link = \Drupal\Core\Link::fromTextAndUrl(t('http:BL homepage at projecthoneypot.org'), $httpblUrl )->toString();

    $form['core'] = array(
      '#type' => 'details',
      '#title' => t('Http:BL'),
      '#description' => t('<p>Http:BL is a Drupal implementation for using the http:BL service provided by Project Honey Pot, to block malicious site traffic.</p><p>To use this capability you will need an http:BL access key, received from Project Honey Pot.  For more information about http:BL, see the @httpbl.</p>', ['@httpbl' => $httpbl_link]),
      '#open' => TRUE,
    );

    // Create link for Access Key info.
    $keyUrl = \Drupal\Core\Url::fromUri('http://www.projecthoneypot.org/faq.php#g');
    $keyUrl_options = [
      'attributes' => [
        'target' => '_blank',
        'title' => t('More info about an access key and how to get one if you do not have one.'),
      ]];
    $keyUrl->setOptions($keyUrl_options);
    $key_link = \Drupal\Core\Link::fromTextAndUrl(t('Access Key'), $keyUrl )->toString();

    $form['core']['httpbl_accesskey'] = array(
      '#type' => 'textfield',
      '#title' => t('http:BL Access Key'),
      '#default_value' => \Drupal::state()->get('httpbl.accesskey') ?: $config->get('httpbl_accesskey'),
      '#description' => t('Your http:BL @access.', ['@access' => $key_link]),
      '#size' => 20,
      '#maxlength' => 12,
    );

    $form['core']['httpbl_check'] = array(
      '#type' => 'radios',
      '#title' => t('Http:BL Blocking'),
      '#default_value' => \Drupal::state()->get('httpbl.check') ?: $config->get('httpbl_check'),
      '#options' => array(
        t('Disabled'),
        t('Comment submissions only. (Blocked comments are unpublished, regardless of comment moderation permissions.)'),
        t('All page requests'),
      ),
      '#description' => t("<p>Determines when host/ip lookups should occur.<p><p><strong>Drush SOS:</strong> there is now a drush command available (<strong>drush sos --stop</strong>) you are encouraged to become familiar with. Should you ever manage to get yourself blacklisted or banned from a site you manage, you can stop all page request blocking until you get your IP white-listed again, and re-start.</p>"),
    );
    // Modify options text with a warning if page_cache is enabled.
    if (\Drupal::hasService('http_middleware.page_cache') ) {
      $form['core']['httpbl_check']['#options'][2] = 'All page requests.  IMPORTANT: Core extension Internal Page Cache (page_cache) has been detected.  Using HttpBL for evaluating page requests is allowed but NOT RECOMMENDED when Internal Page Cache is enabled.<p>Recommended action:  Configure for "Comment submissions only" or uninstall Internal Page Cache.  Note: Use of Dynamic Internal Page Cache is okay.</p>';
    }
    else {
      $form['core']['httpbl_check']['#options'][2] = 'All page requests.';
    }

    // Create link to Project Honeypot home page.
    $homeUrl = \Drupal\Core\Url::fromUri('http://www.projecthoneypot.org');
    $homeUrl_options = [
      'attributes' => [
        'target' => '_blank',
        'title' => t('More info from Project Honeypot.'),
      ]];
    $homeUrl->setOptions($homeUrl_options);
    $home_link = \Drupal\Core\Link::fromTextAndUrl(t('Project Honey Pot'), $homeUrl )->toString();

    $form['honeypot'] = array(
      '#type' => 'details',
      '#title' => t('Honeypot Links'),
      '#description' => t('Your Honeypot (spam trap) settings. For more information, see @ph.', ['@ph' => $home_link]),
      '#open' => TRUE,
    );
  
    $form['honeypot']['httpbl_footer'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add Honeypot to page bottom'),
      '#default_value' => \Drupal::state()->get('httpbl.footer') ?: $config->get('httpbl_footer'),
      '#description' => t('Adds a honeypot link to the page bottom of every page.'),
    );
    
    // Create link to "own Honey Pot."
    $ownUrl = \Drupal\Core\Url::fromUri('http://www.projecthoneypot.org/manage_honey_pots.php');
    $ownUrl_options = [
      'attributes' => [
        'target' => '_blank',
        'title' => t('More info about managing you own honey pots.'),
      ]];
    $ownUrl->setOptions($ownUrl_options);
    $own_link = \Drupal\Core\Link::fromTextAndUrl(t('own Honey Pots'), $ownUrl )->toString();
    // Create link to "quick Honey Pot."
    $quickUrl = \Drupal\Core\Url::fromUri('http://www.projecthoneypot.org/manage_quicklink.php');
    $quickUrl_options = [
      'attributes' => [
        'target' => '_blank',
        'title' => t('More info about using a Quick Link (other people\'s honey pots).'),
      ]];
    $quickUrl->setOptions($quickUrl_options);
    $quick_link = \Drupal\Core\Link::fromTextAndUrl(t('QuickLink'), $quickUrl )->toString();

    $form['honeypot']['httpbl_link'] = array(
      '#type' => 'textfield',
      '#title' => t('Honeypot link'),
      '#default_value' => \Drupal::state()->get('httpbl.link') ?: $config->get('httpbl_link'),
      '#description' => t('Your Honeypot (spam trap) link. This can be one of your @own or a @quick.', ['@own' => $own_link, '@quick' => $quick_link]),
    );
  
    $form['honeypot']['httpbl_word'] = array(
      '#type' => 'textfield',
      '#title' => t('Link word'),
      '#default_value' => \Drupal::state()->get('httpbl.word') ?: $config->get('httpbl_word'),
      '#description' => t('A random word which will be used as a link.'),
    );

    $form['advanced'] = array(
      '#type' => 'details',
      '#title' => t('Logs, Stats, Threshold and Storage Settings'),
      '#open' => TRUE,
    );
  
    $form['advanced']['httpbl_log'] = array(
      '#type' => 'radios',
      '#title' => t('Http:BL logging'),
      '#default_value' => \Drupal::state()->get('httpbl.log') ?: $config->get('httpbl_log'),
      '#options' => array(
        t('Quiet - Log errors.'),
        t('Minimal - Logs warning & notice, for positive lookups and admin actions.'),
        t('Verbose - Logs debug and info messages.  Useful for testing and gaining trust.'),
      ),
      '#description' => t('Verbose logging <strong>not recommended for production</strong>. If http:BL is configured for blocking all page requests, verbose logging will log 1 - 3 messages per every page request!'),
    );

    // Create link to status report.
    $status_link = \Drupal\Core\Link::fromTextAndUrl(t('Status Report'), \Drupal\Core\Url::fromRoute('system.status'))->toString();

    $form['advanced']['httpbl_stats'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable statistics'),
      '#default_value' => \Drupal::state()->get('httpbl.stats') ?: $config->get('httpbl_stats'),
      '#description' => t('Whether to enable counting of positive lookups. Statistics show a simple, historical running total on the @status page.', ['@status' => $status_link]),
      );
    
    $form['advanced']['httpbl_storage'] = array(
      '#type' => 'radios',
      '#title' => t('Results Storage'),
      '#default_value' => \Drupal::state()->get('httpbl.storage') ?: $config->get('httpbl_storage'),
      '#options' => array(
        t('Off - Minimal, time consuming defense that doesn\'t "remember" repeat visitors.'),
        t('Hosts - Stores results as Host entities, for faster, local lookup of repeat visitors.'),
        t('Hosts + Auto-banning: Blacklisted hosts are added to Drupal\'s core Ban table, where they can be managed by Http:BL. &nbsp;&nbsp; (<strong>Most secure option in all cases!</strong>)'),
      ),
      '#description' => t('Saving results means local lookups are much faster!  Otherwise, all checking and blocking is handled via an external database service (Project Honey Pot). Also, please note that without storage, grey-listed IPs that fail the session white-list challenge cannot be automatically blacklisted or banned. They will continue to be challenged on every subsequent visit.'),
    );
  
    $blacklist_threshold_options = httpbl_map_assoc(array(50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100, 200, 255));
    $form['advanced']['httpbl_black_threshold'] = array(
      '#type' => 'select',
      '#title' => t('Blacklisting Threshhold'),
      '#default_value' => \Drupal::state()->get('httpbl.black_threshold') ?: $config->get('httpbl_black_threshold') ?: HTTPBL_THRESHOLD_BLACK,
      '#options' => $blacklist_threshold_options,
      '#description' => t('Threat level threshold above which a user is blacklisted.  The default threat is 50, and that\'s a good place to start. Any IP that has scored a threat of 50 or higher is looking for trouble.  While you may have a need to go higher, note that anything getting close to 100 is effectively turning off this service.'),
    );
  
    $form['advanced']['httpbl_message_black'] = array(
      '#type' => 'textarea',
      '#title' => t('Blacklist message'),
      '#default_value' => \Drupal::state()->get('httpbl.message_black') ?: '<h1>403 HTTP_FORBIDDEN</h1>Your IP address (@ip) is forbidden anywhere on this website.  It has been blacklisted; based on a profile lookup of this IP address at <a href="@ipurl">Project Honeypot</a>.@honeypot',
      '#description' => t("The message visitors will see when their IP is blacklisted. <em>@ip</em> will be replaced with the visitor's IP, <em>@ipurl</em> with a link to the Project Honeypot information page for that IP, <em>@honeypot</em> with your Honeypot link."),
    );
  
    $greylist_threshold_options = httpbl_map_assoc(array(1, 2, 3, 4, 5, 10, 15, 20, 25, 30, 35, 40, 45));
    $form['advanced']['httpbl_grey_threshold'] = array(
      '#type' => 'select',
      '#title' => t('Grey-listing Threat Level Threshhold'),
      '#default_value' => \Drupal::state()->get('httpbl.grey_threshold') ?: $config->get('httpbl_grey_threshold') ?: HTTPBL_THRESHOLD_GREY,
      '#options' => $greylist_threshold_options,
      '#description' => t('Threat level threshold above which a visitor is grey-listed.  Any score at or below this level will be considered "safe."  Use this to fine-tune the Project Honey Pot threat-levels that you are willing to permit for "safe" access to your site.  Every site has unique requirements and tolerance levels, and while the lowest threshold is best, you may have very valuable, well intentioned users with slightly compromised IPs, resulting in frequent grey-listing.'),
    );
  
    $form['advanced']['httpbl_message_grey'] = array(
      '#type' => 'textarea',
      '#title' => t('Greylist message'),
      '#default_value' => \Drupal::state()->get('httpbl.message_grey') ?: '<h1>428 HTTP_PRECONDITION_REQUIRED</h1><p>Your IP address (@ip) has been identified as a <em>possible</em> source of suspicious, robotic traffic and has been grey-listed; based on your IP profile at <a href="@ipurl">Project Honeypot</a>.</p><p>If you are a human visitor who can read easy instructions, you may attempt a challenge request for session based white-listing <a href="@whitelistUrl">HERE</a>.</p>@honeypot',

      '#description' => t("The message visitors will see when their IP is greylisted. <em>@ip</em> will be replaced with the visitor's IP, <em>@ipurl</em> with a link to the Project Honeypot profile that indicated problems for that particular IP. <em>@honeypot</em> Will be replaced with your Honeypot link. <em>@whitelistUrl</em> with the internal whitelist request URL."),
    );
  
    // Get the date service.
    $date_service = \Drupal::service('date.formatter');
    $storage_period = httpbl_map_assoc(array(10800, 21600, 32400, 43200, 86400, 172800, 259200, 604800, 1209600, 2419200), array($date_service, 'formatInterval'));
    $form['advanced']['httpbl_safe_offset'] = array(
      '#type' => 'select', 
      '#title' => t('Safe Visitor Storage Expires in...'),
      '#default_value' => \Drupal::state()->get('httpbl.safe_offset') ?: $config->get('httpbl_safe_offset') ?: 10800,
      '#options' => $storage_period,
      '#description' => t('How long to store safe or white-listed IPs before expiring.'),
    );
  
    $storage_period2 = httpbl_map_assoc(array(43200, 86400, 172800, 259200, 604800, 1209600, 1814400, 2419200), array($date_service, 'formatInterval'));
    $form['advanced']['httpbl_greylist_offset'] = array(
      '#type' => 'select', 
      '#title' => t('Grey Listed Visitor Storage Expires in...'),
      '#default_value' => \Drupal::state()->get('httpbl.greylist_offset') ?: $config->get('httpbl_greylist_offset') ?: 86400,
      '#options' => $storage_period2,
      '#description' => t('How long to store grey-listed IPs before expiring.'),
    );
  
    $storage_period3 = httpbl_map_assoc(array(604800, 1209600, 1814400, 2419200, 7257600, 15724800, 31536000, 63072000, 94608000), array($date_service, 'formatInterval'));
    $form['advanced']['httpbl_blacklist_offset'] = array(
      '#type' => 'select', 
      '#title' => t('Black Listed Visitor Storage Expires in...'),
      '#default_value' => \Drupal::state()->get('httpbl.blacklist_offset') ?: $config->get('httpbl_blacklist_offset') ?: 31536000,
      '#options' => $storage_period3,
      '#description' => t('How long to store black-listed IPs before expiring. (Also applies when same IPs banned in Drupal core ban_ip table, when applicable.)'),
    );
  
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $key = $values['httpbl_accesskey'];

    if ($values['httpbl_check'] && !$key) {
      $form_state->setErrorByName('httpbl_accesskey', $this->t('You must enter a valid access key to enable blacklist checks.'));
    }
    
    if ($values['httpbl_footer'] && !$values['httpbl_link']) {
      $form_state->setErrorByName('httpbl_link', $this->t('You must enter a link to be able to add it to the page bottom.'));
    }
    
    if ($values['httpbl_check'] > HTTPBL_CHECK_NONE && $key) {
      // Key should be 12 lowercase alpha characters.
      // There's no unicode allowed, so we're not using drupal_strlen().
      // ereg is deprecated.  Now using preg_grep instead?
      if (preg_grep('/[^a-z]/', array($key)) || strlen($key) != 12) {
        $form_state->setErrorByName('httpbl_accesskey', $this->t('Your access key is formatted incorrectly.'));
      }
      elseif (!count($form_state->getErrors())) {
        // Do a test lookup (with known result).
        // Not sure we are really testing a valid key?
        $evaluator = \Drupal::service('httpbl.evaluator');
        $lookup = $evaluator->httpbl_dnslookup('127.1.80.1', $key);

        if (!$lookup || $lookup['threat'] != 80) {
          $form_state->setErrorByName('httpbl_accesskey', $this->t('Testcase failed. This either means that your access key is incorrect or that there is a problem in your DNS system.'));
        }
        else {
          drupal_set_message(t('Http:BL tested access to Project Honeypot and completed successfully.'));
        }
      }
    }

    // If Auto-ban storage selected, ensure there is a Ban Service.
    if ($values['httpbl_storage'] == HTTPBL_DB_HH_DRUPAL && !\Drupal::hasService('ban.ip_manager') ) {
      \Drupal::service('module_installer')->install(['ban']);
    }
    if ($values['httpbl_check'] > HTTPBL_CHECK_NONE && $values['httpbl_storage'] == HTTPBL_DB_HH_DRUPAL && \Drupal::hasService('ban.ip_manager') ) {
      drupal_set_message(t('Auto-banning is enabled!'));
    }
    // Set error message if configured for page checking and Internal Page
    // Cache service is detected.
    if ($values['httpbl_check'] == HTTPBL_CHECK_ALL && \Drupal::hasService('http_middleware.page_cache') ) {
      drupal_set_message(t('Core extension Internal Page Cache (page_cache) has been detected. Using HttpBL for evaluating page requests is allowed but NOT RECOMMENDED when Internal Page Cache is enabled.'), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  
    $values = $form_state->getValues();
    $this->config('httpbl.settings')
      ->set('httpbl_accesskey', $values['httpbl_accesskey'])
      ->set('httpbl_check', (int)$values['httpbl_check'])
      ->set('httpbl_footer', $values['httpbl_footer'])
      ->set('httpbl_link', $values['httpbl_link'])
      ->set('httpbl_word', $values['httpbl_word'])
      ->set('httpbl_log', (int)$values['httpbl_log'])
      ->set('httpbl_stats', (int)$values['httpbl_stats'])
      ->set('httpbl_storage', (int)$values['httpbl_storage'])
      ->set('httpbl_black_threshold', $values['httpbl_black_threshold'])
      ->set('httpbl_message_black', $values['httpbl_message_black'])
      ->set('httpbl_grey_threshold', $values['httpbl_grey_threshold'])
      ->set('httpbl_message_grey', $values['httpbl_message_grey'])
      ->set('httpbl_safe_offset', $values['httpbl_safe_offset'])
      ->set('httpbl_greylist_offset', $values['httpbl_greylist_offset'])
      ->set('httpbl_blacklist_offset', $values['httpbl_blacklist_offset'])
     ->save();
      
    // Use the form values to set some run-time variables.
    \Drupal::state()->set('httpbl.accesskey', $values['httpbl_accesskey']);
    \Drupal::state()->set('httpbl.check', (int)$values['httpbl_check']);
    \Drupal::state()->set('httpbl.footer', $values['httpbl_footer']);
    \Drupal::state()->set('httpbl.link', $values['httpbl_link']);
    \Drupal::state()->set('httpbl.word', $values['httpbl_word']);
    \Drupal::state()->set('httpbl.log', (int)$values['httpbl_log']);
    \Drupal::state()->set('httpbl.stats', (int)$values['httpbl_stats']);
    \Drupal::state()->set('httpbl.storage', (int)$values['httpbl_storage']);
    \Drupal::state()->set('httpbl.black_threshold', $values['httpbl_black_threshold']);
    \Drupal::state()->set('httpbl.message_black', $values['httpbl_message_black']);
    \Drupal::state()->set('httpbl.grey_threshold', $values['httpbl_grey_threshold']);
    \Drupal::state()->set('httpbl.message_grey', $values['httpbl_message_grey']);
    \Drupal::state()->set('httpbl.safe_offset', $values['httpbl_safe_offset']);
    \Drupal::state()->set('httpbl.greylist_offset', $values['httpbl_greylist_offset']);
    \Drupal::state()->set('httpbl.blacklist_offset', $values['httpbl_blacklist_offset']);

    //drupal_flush_all_caches();
  }
}
