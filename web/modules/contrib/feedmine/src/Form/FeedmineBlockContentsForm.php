<?php
/**
 * @file
 * Contains \Drupal\feedmine\Form\FeedmineBlockContentsForm.
 */

namespace Drupal\feedmine\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implements an test form.
 */
class FeedmineBlockContentsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'feedmine_block_contents';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Verify if Feedmine settings have been configured.
    $rm_pid = \Drupal::config('feedmine.settings')->get('feedmine_rmprojectid');
    if (!isset($rm_pid)) {
      // Display a link to the feedmine module config page.
      $text = t('Click here to configure Redmine settings and enable Feedmine.');
      $path = '/admin/config/feedmine/feedmine_settings';
      $form['feedmine_not_configured'] = array(
        '#type' => 'item',
        '#title' => 'NOTICE',
        '#markup' => '<a href=' . $path . '>' . $text . '</a>'
      );
    }
    else {
    // Display the form to submit feedback.
    $form['feedmine_tracker'] = array(
      '#type' => 'select',
      '#title' => t('Type'),
      '#description' => t('Type of feedback'),
      '#default_value' => 'bug',
      '#options' => array(
        'bug' => t('Bug'),
        'feature' => t('Feature'),
        'support' => t('Support'),
      ),
    );
    $form['feedmine_subject'] = array(
      '#type' => 'textfield',
      '#size' => 20,
      '#title' => t('Subject'),
      '#required' => TRUE,
    );
    //drupal_set_message(print_r($user , TRUE));
    $current = 'Current User : ' . \Drupal::currentUser()->getAccount()->getDisplayName();
    $current .= "\n" . "Current User Role: " . implode(', ', \Drupal::currentUser()->getAccount()->getRoles());
    $current .= "\n" . "Current URL: " . $_SERVER['HTTP_HOST'] . \Drupal::request()->getRequestUri();
    $form['feedmine_feedback'] = array(
      '#type' => 'textarea',
      '#cols' => 5,
      '#rows' => 5,
      '#title' => t('Feedback'),
      '#default_value' => $current,
      '#description' => t('Please leave a detailed description.'),
      '#required' => TRUE,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Thank You')
    );
    }
  // Return the block contents.
  return $form;
  }

    /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve Redmine API settings
    $rm_base_url = \Drupal::config('feedmine.settings')->get('feedmine_rmurl');
    $rm_api_key  = \Drupal::config('feedmine.settings')->get('feedmine_rmapikey');
    $rm_uid      = \Drupal::config('feedmine.settings')->get('feedmine_rmuid');
    $rm_proj_id  = \Drupal::config('feedmine.settings')->get('feedmine_rmprojectid');

    $rmi_data = array();
    $rmi_data['user']             = \Drupal::currentUser()->getAccount();
    $rmi_data['destination']      = drupal_get_destination();
    $rmi_data['server_variable']  = $_SERVER;
    $rmi_data['feedback_tracker'] = $form_state->getValue('feedmine_tracker');
    $rmi_data['feedback_subject'] = $form_state->getValue('feedmine_subject');
    $rmi_data['feedback']         = $form_state->getValue('feedmine_feedback');

    // Attach a file to the issue.
    $uploads = array();
    // Retrieve a token for the file and refrence with the new issue
    $token = feedmine_issue_attach($rmi_data);
    if(isset($token)){
      $uploads['upload'] = array (
        'token' => $token,
        'filename' => 'fmuser_' . \Drupal::currentUser()->getAccount()->id() . '_on_' . time() . '.json',
        'content_type' => 'application/json',
      );
    }
    
    //$uploads = array();  // To test TODO

    // Format new issue data.
    $issue = array();
    $issue['issue'] = array(
      'project_id'     => $rm_proj_id,
      'subject'        => $rmi_data['feedback_subject'],
      'description'    => $rmi_data['feedback'],
      'assigned_to_id' => $rm_uid,
      'uploads'        => $uploads,
    );
    $issue_json = json_encode($issue);

    // Prepare request.
    $options = array(
      'headers' => array(
        'Content-Type' => 'application/json',
        'X-Redmine-API-Key' => $rm_api_key,
      ),
      'body' => $issue_json,
    );
    $rm_endpoint = '/issues.json';
    $request_url = $rm_base_url . $rm_endpoint;
//print_r($options); die();
    // Submit request and get response.
    try {
      $response = \Drupal::httpClient()->post($request_url, $options);
      $data = (string) $response->getBody();
      if(!empty($data)) {
        drupal_set_message('Thanks for your feedback, an issue has been created.', 'status');
      }
    }
    catch (RequestException $e) {
      \Drupal::logger('feedmine')->error('Some Problems in issues creation',$e);
      return FALSE;
    }

  }

}