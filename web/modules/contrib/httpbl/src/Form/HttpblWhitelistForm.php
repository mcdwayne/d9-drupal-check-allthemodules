<?php

namespace Drupal\httpbl\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\httpbl\HttpblResponseInterface;
use Drupal\httpbl\HttpblEvaluatorInterface;
use Drupal\httpbl\Logger\HttpblLogTrapperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays banned IP addresses.
 */
class HttpblWhitelistForm extends FormBase {

  /**
   * The Httpbl Evaluator.
   *
   * @var \Drupal\httpbl\HttpblEvaluatorInterface
   */
  protected $httpblEvaluator;

  /**
   * The Httpbl Response.
   *
   * @var \Drupal\httpbl\HttpblResponseInterface
   */
  protected $httpblResponse;

  /**
   * A logger arbitration instance.
   *
   * @var \Drupal\httpbl\Logger\HttpblLogTrapperInterface
   */
  protected $logTrapper;

  /**
   * White-list form services.
   *
   * @param \Drupal\httpbl\HttpblEvaluatorInterface         $httpblEvaluator
   * @param \Drupal\httpbl\HttpblResponseInterface          $httpblResponse
   * @param \Drupal\httpbl\Logger\HttpblLogTrapperInterface $logTrapper
   */
  public function __construct(HttpblEvaluatorInterface $httpblEvaluator, HttpblResponseInterface $httpblResponse, HttpblLogTrapperInterface $logTrapper) {
    $this->httpblEvaluator = $httpblEvaluator;
    $this->httpblResponse = $httpblResponse;
    $this->logTrapper = $logTrapper;
 }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('httpbl.evaluator'),
      $container->get('httpbl.response'),
      $container->get('httpbl.logtrapper')
   );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'httpbl_whitelist_form';
  }

  /**
   * {@inheritdoc}
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['advise'] = array(
      '#markup' => '<div class="httpbl-advice form-item">' . $this->t('Please note:  Session white-listing requires cookies to be enabled.') . '</div>',
    );

    $form['reason'] = array(
      '#type' => 'textarea',
      '#title' => t('Reason you were blocked. (It\'s okay to say you don\'t know if you don\'t)'),
      '#size' => 60,
      '#required' => TRUE,
    );

    $form['block'] = array(
      '#type' => 'textfield',
      '#title' => t('LEAVE THIS BLANK! (This is where robotic spammers fail, because they don\'t actually read!)'),
      '#size' => 15,
    );
  
    $form['leave'] = array(
      '#type' => 'textfield',
      '#size' => 30,
      '#attributes' => array('style' => 'display: none')
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('White-list request'),
    );

    // Save incoming original destination as a hidden form value.
    // This has never worked.  Need a new approach.
    // @todo - Figure out a way to return visitor to original request if they 
    // pass the challenge.
    $form['arrival'] = array(
      '#type' => 'hidden',
      //'#default_value' => $previousUrl,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $ip = $this->getRequest()->getClientIP();
    $project_link = $this->httpblEvaluator->projectLink($ip);
    $values = $form_state->getValues();
  
    // If the forbidden areas have any value, visitor has failed the challenge. 
    if ($values['block'] || $values['leave']) {

      // Kill any white-listed session for this visitor.
      if (isset($_SESSION['httpbl_status'])) {
        unset($_SESSION['httpbl_status']);
      }

      // If we are storing visitor lookup results...
      if (\Drupal::state()->get('httpbl.storage') == HTTPBL_DB_HH || \Drupal::state()->get('httpbl.storage') == HTTPBL_DB_HH_DRUPAL) {
        
        // Update them from greylisted to blacklisted (they will also be auto-
        // banned or not, per configuration option).
        $this->httpblEvaluator->updateIpLocalStatus($ip, HTTPBL_LIST_BLACK, $offset = \Drupal::state()->get('httpbl.blacklist_offset'));
        \Drupal::state()->get('httpbl.blacklist_offset');
        
        // Get the blacklist date offset and prepare a readable date interval for
        // a message to user.
        $offset = \Drupal::state()->get('httpbl.blacklist_offset');
        $return_date = \Drupal::service('date.formatter')->formatInterval($offset, $granularity = 2, $langcode = NULL);

        // Log the visitor failure.
        //$this->logTrapper->trapNotice('@ip blacklisted for @return_date for failing session white-list challenge.', ['@ip' => $ip, '@return_date' => $return_date]);
        $this->logTrapper->trapNotice('@ip blacklisted for @return_date for failing session white-list challenge. Source: @source.',
          [
            '@ip' => $ip,
            '@return_date' => $return_date,
            '@source' => HTTPBL_CHALLENGE_FAILURE,
            'link' => $project_link,
          ]);

        // Build failed/blacklisted response to visitor.  It will say how long
        // they've been blacklisted for (the configured amount of time).
        $failureResponse = $this->httpblResponse->challengeFailureBlacklisted($ip, $return_date);
        print $failureResponse;
        // Buh-bye!
        exit();
      }
      else {
        // Not storing visitor lookups.  Visitor will remain greylisted (per 
        // Project Honepot results) and in white-list challenge purgatory. So,
        // simply inform them of the failure. 
        $this->logTrapper->trapWarning('@ip failed session white-list request.  Source: @source.',
          [
          '@ip' => $ip,
          '@source' => HTTPBL_CHALLENGE_FAILURE,
          'link' => $project_link
          ]
        );

        $failureResponse = $this->httpblResponse->challengeFailurePurgatory();
        print $failureResponse;
        exit();
      }
    }
    // This challenge was a success.  Visitor will be session white-listed on submit.
    $this->logTrapper->trapNotice('@ip success at white-list challenge. Source: @source.',
      ['@ip' => $ip,
       '@source' => HTTPBL_CHALLENGE_SUCCESS,
      'link' => $project_link
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Unset the White-list Challenge
    if (isset($_SESSION['httpbl_challenge'])) {
      unset($_SESSION['httpbl_challenge']);
    }

    // Set this visitor as Session White-listed
    $_SESSION['httpbl_status'] = 'session_whitelisted';
    drupal_set_message(t('Success! Your current session has been white-listed.'), 'status', FALSE);

    // Setup redirect to original request Uri
    $url = Url::fromRoute('<front>');
    $form_state->setRedirectUrl($url);

  }

}