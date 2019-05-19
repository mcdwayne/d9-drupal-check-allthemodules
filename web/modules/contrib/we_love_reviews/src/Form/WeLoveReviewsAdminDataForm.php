<?php

/**
 * @file
 * Contains \Drupal\we_love_reviews\Form\ReputationcrmAdminDataForm.
 */

namespace Drupal\we_love_reviews\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class WeLoveReviewsAdminDataForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'we_love_reviews_admin_data_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('we_love_reviews.data');

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
    return ['we_love_reviews.data'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $config = $this->config('we_love_reviews.data');

    $form['business_name'] = [
      '#title' => t('Business Name'),
      '#type' => 'textfield',
      '#default_value' => $config->get('business_name'),
      '#size' => 60,
      '#description' => t('Enter the Business Name.'),
    ];

     $form['business_address'] = [
      '#title' => t('Address'),
      '#type' => 'textfield',
      '#default_value' => $config->get('business_address'),
      '#size' => 60,
      '#description'  => t('Enter the Postal Address.'),  
    ];

    $form['business_town'] = [
      '#title' => t('City'),
      '#type' => 'textfield',
      '#default_value' => $config->get('business_town'),
      '#size' => 60,
      '#description'  => t('Enter the City.'),  
    ];

    $form['business_state'] = [
      '#title' => t('State / Province'),
      '#type' => 'textfield',
      '#default_value' => $config->get('business_state'),
      '#size' => 60,
       '#description'  => t('Enter the State / Province.'), 
    ];

    $form['business_zip'] = [
      '#title' => t('Postal code'),
      '#type' => 'textfield',
      '#default_value' => $config->get('business_zip'),
      '#size' => 60,
       '#description'  => t('Enter the Postal Code.'), 
    ];

    $form['business_country'] = [
      '#title' => t('Country'),
      '#type' => 'textfield',
      '#default_value' => $config->get('business_country'),
      '#size' => 60,
      '#description' => t('Enter the Country'),
    ];

    $form['business_phone'] = [
      '#title' => t('Phone #'),
      '#type' => 'textfield',
      '#default_value' => $config->get('business_phone'),
      '#size' => 60,
      '#description' => t('Enter the Phone # in E.196 international format: +country code and no leading 0 in the number if any in your country.'),
    ];

    $form['business_email'] = [
      '#title' => t('Email Address'),
      '#type' => 'textfield',
      '#default_value' => $config->get('business_email'),
      '#size' => 60,
      '#description' => t('Enter the Email Address'),
    ];

    $form['business_logourl'] = [
      '#title' => t('Logo image URL'),
      '#type' => 'textfield',
      '#default_value' => $config->get('business_logourl'),
      '#size' => 60,
      '#description' => t('Enter the Company Logo URL. Logo must be in square format and in high resolution (example: 690 pixels x 690 pixels).'),
    ];

    $form['business_url'] = [
      '#title' => t('Company Website URL'),
      '#type' => 'textfield',
      '#default_value' => $config->get('business_url'),
      '#size' => 60,
      '#description' => t('Enter the Company Website URL.'),
    ];

    $form['business_sameas'] = [
      '#title' => t('Review page URL'),
      '#type' => 'textfield',
      '#default_value' => $config->get('business_sameas'),
      '#size' => 60,
       '#description' => t('<a href="@companies-database" title="Company / Location ID" target="_blank">Click here</a> to view the datatable with all Companies / Locations.<br>- In the datatable, locate the Company / Location and at the end of the line, click on the <strong>★&nbsp;Review Page</strong> button to open it.<br>- Copy the Review Page URL from the browser URL bar and paste it here.<br>- After pasting the URL, we recommend that you delete the language part of the URL (/en). Example:<br>&nbsp;&nbsp;&nbsp;https://welove.reviews/en/Calder-Motor-Company-EH27-8DF-East-Calder-21496426 <strong>should be saved as</strong>:<br>&nbsp;&nbsp;&nbsp;https://welove.reviews/Calder-Motor-Company-EH27-8DF-East-Calder-21496426', 
      [
      '@companies-database'  => 'https://reputationcrm.com/companies-locations',
      ]),
    ];

    return parent::buildForm($form, $form_state);
  }

}
?>