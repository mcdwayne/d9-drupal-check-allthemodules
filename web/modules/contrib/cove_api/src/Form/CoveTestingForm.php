<?php

namespace Drupal\cove_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\cove_api\CoveRequest;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HTMLCommand;

/**
 * Class CoveTestingForm.
 *
 * @package Drupal\cove_api\Form
 */
class CoveTestingForm extends FormBase {


  /**
   * Drupal\cove_api\CoveRequest definition.
   *
   * @var Drupal\cove_api\CoveRequest
   */
  protected $cove_api_request;
  public function __construct(
    CoveRequest $cove_api_request
  ) {
    $this->cove_api_request = $cove_api_request;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cove_api.request')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cove_testing_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['method'] = array(
      '#type' => 'select',
      '#title' => $this->t('Method'),
      '#options' => array(
        'programs' => $this->t('Programs'),
        'videos' => $this->t('Videos'),
        'categories' => $this->t('Categories'),
        'graveyard' => $this->t('Graveyard'),
      ),
      '#description' => $this->t('Select the main method to call'),
    );
    $form['method_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('ID (optional)'),
      '#default_value' => '',
      '#size' => 5,
      '#maxlength' => 4,
      '#required' => FALSE,
      '#description' => $this->t('You can provide an ID to use with the method above.'),
    );

    $form['offset'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Offset'),
      '#size' => 5,
      '#maxlength' => 4,
      '#required' => FALSE,
    );

    // Show only the parameters that make sense with the method
    $form['parameters'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Filters'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );
    $form['parameters']['filter_nola_root'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('NOLA Root'),
      '#description' => $this->t('Provide NOLA root for filtering'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array('value' => 'programs'),
        ),
        'enabled' => array(
          ':input[name="method"]' => array('value' => 'programs'),
        ),
      ),
    );
    $form['parameters']['filter_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('Show title'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array(
              array('value' => 'programs'),
              array('value' => 'videos'),
            ),
        ),
        'enabled' => array(
          ':input[name="method"]' => array(
            array('value' => 'programs'),
            array('value' => 'videos'),
          ),
        ),
      ),
    );
    $form['parameters']['filter_producer__name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Producer Name'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array('value' => 'programs'),
        ),
        'enabled' => array(
          ':input[name="method"]' => array('value' => 'programs'),
        ),
      ),
    );
    $form['parameters']['filter_guid'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Merlin GUID'),
      '#description' => $this->t('Provide Merlin GUID for filtering'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
        'enabled' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
      ),
    );
    $form['parameters']['filter_program'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Program ID'),
      '#description' => $this->t('Provide Program ID for filtering'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
        'enabled' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
      ),
    );
    $form['parameters']['filter_program__title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Program Title'),
      '#description' => $this->t('Provide Program Title for filtering'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
        'enabled' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
      ),
    );
    $form['parameters']['filter_availability_status'] = array(
      '#type' => 'select',
      '#options' => array(
        '' => $this->t('-None-'),
        'Not Available' => $this->t('Not Available'),
        'Available' => $this->t('Available'),
        'Expired' => $this->t('Expired'),
      ),
      '#title' => $this->t('Avialability'),
      '#description' => $this->t('Filter by avialablity'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
        'enabled' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
      ),
    );
    $form['parameters']['filter_record_last_updated_datetime__gt'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Last Modified Date'),
      '#description' => $this->t('YYYY-MM-DD'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
        'enabled' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
      ),
    );
    $form['parameters']['filter_type'] = array(
      '#type' => 'select',
      '#options' => array(
        '' => $this->t('-None-'),
        'Episode' => $this->t('Episode'),
        'Clip' => $this->t('Clip'),
        'Promotion' => $this->t('Promotion'),
        'Interstitial' => $this->t('Interstitial'),
        'Other' => $this->t('Other'),
      ),
      '#title' => $this->t('Video Type'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
        'enabled' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
      ),
    );
    $form['parameters']['exclude_type'] = array(
      '#type' => 'select',
      '#options' => array(
        '' => $this->t('-None-'),
        'Episode' => $this->t('Episode'),
        'Clip' => $this->t('Clip'),
        'Promotion' => $this->t('Promotion'),
        'Interstitial' => $this->t('Interstitial'),
        'Other' => $this->t('Other'),
      ),
      '#title' => $this->t('Exclude Video Type'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
        'enabled' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
      ),
    );
    $form['parameters']['filter_tp_media_object_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Platform Media Object id'),
      '#description' => $this->t('Use the Platform Media Object id to find a video'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
        'enabled' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
      ),
    );
    $form['parameters']['filter_mediafile_set__video_encoding__mime_type'] = array(
      '#title' => $this->t('Encoding'),
      '#description' => $this->t('Return only videos that have matching encoding value'),
      '#type' => 'select',
      '#options' => array(
        '' => $this->t('-None-'),
        'video/FLV' => $this->t('FLV (Kids Videos'),
        'video/mp4' => $this->t('MP4'),
        'application/x-mpegURL' => $this->t('MPEG URL'),
      ),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
        'enabled' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
      ),
    );
    $form['parameters']['content_region'] = array(
      '#title' => $this->t('Content Region'),
      '#description' => $this->t('Return either national or local content. <em>This filter is useful for Passport content</em>'),
      '#type' => 'select',
      '#options' => array(
        '' => $this->t('-None-'),
        'national' => $this->t('National'),
        'local' => $this->t('Local'),
      ),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
        'enabled' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
      ),
    );
    $form['parameters']['audience'] = array(
      '#title' => $this->t('Audience'),
      '#description' => $this->t('Return videos available to public, all members and station members.'),
      '#type' => 'select',
      '#options' => array(
        '' => $this->t('-None-'),
        'public' => $this->t('Public'),
        'station_members' => $this->t('Station Members'),
        'all_members' => $this->t('All Members'),
      ),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
        'enabled' => array(
          ':input[name="method"]' => array('value' => 'videos'),
        ),
      ),
    );
    $form['parameters']['fields']['associated_images'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('include images'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array(
            array('value' => 'programs'),
            array('value' => 'videos'),
          ),
        ),
        'enabled' => array(
          ':input[name="method"]' => array(
            array('value' => 'programs'),
            array('value' => 'videos'),
          ),
        ),
      ),
    );
    $form['parameters']['fields']['tags'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('include tags'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array('value' => 'programs'),
        ),
        'enabled' => array(
          ':input[name="method"]' => array('value' => 'programs'),
        ),
      ),
    );
    $form['parameters']['fields']['categories'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('include categories'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array('value' => 'programs'),
        ),
        'enabled' => array(
          ':input[name="method"]' => array('value' => 'programs'),
        ),
      ),
    );
    $form['parameters']['fields']['geo_profile'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('include geo_profile'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array('value' => 'programs'),
        ),
        'enabled' => array(
          ':input[name="method"]' => array('value' => 'programs'),
        ),
      ),
    );
    $form['parameters']['fields']['producer'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('include producer'),
      '#states' => array(
        'visible' => array(
          ':input[name="method"]' => array('value' => 'programs'),
        ),
        'enabled' => array(
          ':input[name="method"]' => array('value' => 'programs'),
        ),
      ),
    );

    // use an AJAX callback to display results without refreshing the page
    $form['get_response'] = array(
      '#type' => 'button',
      '#ajax' => array(
        'callback' => 'Drupal\cove_api\Form\CoveTestingForm::submitCallback',
        'event' => 'click',
        'progress' => array(
          'type' => 'throbber',
          'message' => 'Getting response',
        ),

      ),
      '#value' => $this->t('Get response'),
    );

    // results will be displayed here
    $form['box'] = array(
      '#type' => 'markup',
      '#prefix' => '<div id="box">',
      '#suffix' => '</div>',
      '#markup' => '',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  public function submitCallback(array &$form, FormStateInterface $form_state) {
    // remove standard form api elements, leaving only submitted data
    $form_state->cleanValues();

    $args = array();
    foreach ($form_state->getValues() as $key => $value) {
      if ($key == 'method') {
        $method = $value;
      }
      else {
        if (!empty($value)) {
          $args[$key] = $value;
        }
      }
    }

    // now that we have the method and the args, call the API
    $request = new CoveRequest();
    $response = $request->request($method, $args);
    $output = '<pre>' . print_r($response, 1) . '</pre>';

    // Instantiate an AjaxResponse Object to return.
    $ajax_response = new AjaxResponse();

    // ValCommand does not exist, so we can use InvokeCommand.
    $ajax_response->addCommand(new HtmlCommand('#box', $output));


    // Return the AjaxResponse Object.
    return $ajax_response;
  }

}
