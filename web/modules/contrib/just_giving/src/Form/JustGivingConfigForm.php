<?php

namespace Drupal\just_giving\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\just_giving\JustGivingSearch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class JustGivingConfigForm.
 */
class JustGivingConfigForm extends ConfigFormBase {

  /**
   * Drupal\just_giving\JustGivingClient definition.
   *
   * @var \Drupal\just_giving\JustGivingClient
   */
  protected $justGivingSearch;

  /**
   * JustGivingConfigForm constructor.
   *
   * @param \Drupal\just_giving\Form\JustGivingSearch $jg_search
   */
  public function __construct(JustGivingSearch $jg_search) {
//    $this->justGivingSearch = $jg_search->getCharityList();
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('just_giving.search')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'just_giving.justgivingconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'just_giving_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('just_giving.justgivingconfig');
    $form['environments'] = [
      '#type' => 'select',
      '#title' => $this->t('Environments'),
      '#description' => $this->t('Choose between sandbox and production environment endpoints'),
      '#options' => [
        'https://api.staging.justgiving.com/' => $this->t('https://api.staging.justgiving.com/'),
        'https://api.justgiving.com/' => $this->t('https://api.justgiving.com/'),
      ],
      '#size' => 1,
      '#default_value' => $config->get('environments'),
    ];
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App ID'),
      '#description' => $this->t('Just Giving App ID: https://developer.justgiving.com/admin/applications'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
    ];
    $form['api_version'] = [
      '#type' => 'select',
      '#title' => $this->t('API Version'),
      '#description' => $this->t('Choose API version (currently only version 1 available)'),
      '#options' => ['1' => $this->t('1')],
      '#size' => 1,
      '#default_value' => $config->get('api_version'),
    ];
    $form['charity_id'] = [
      '#type' => 'textfield',
      '#size' => 20,
      '#title' => $this->t('Charity Id'),
      '#autocomplete_route_name' => 'just_giving.charitiy_search_autocomplete',
      '#autocomplete_route_parameters' => array('field_name' => 'charity_id', 'count' => 10),
      '#description' => $this->t(
        'Enter Charity ID or the charity name to choose the Charity ID that campaigns will be associated with, 
      found here: https://www.justgiving.com/charities/Settings/charity-profile'
      ),
      '#default_value' => $config->get('charity_id'),
    ];
    // TODO remove this, there are not required.
    //    $form['username'] = [
    //      '#type' => 'textfield',
    //      '#title' => $this->t('Username'),
    //      '#description' => $this->t('Just Giving developer account username'),
    //      '#maxlength' => 64,
    //      '#size' => 64,
    //      '#default_value' => $config->get('username'),
    //      '#required' => TRUE,
    //    ];
    //    $form['password'] = [
    //      '#type' => 'password',
    //      '#title' => $this->t('Password'),
    //      '#description' => $this->t('Just Giving developer account password'),
    //      '#maxlength' => 64,
    //      '#size' => 64,
    //      '#default_value' => $config->get('password'),
    //      '#required' => TRUE,
    //    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('just_giving.justgivingconfig')
      ->set('environments', $form_state->getValue('environments'))
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('api_version', $form_state->getValue('api_version'))
      ->set('charity_id', $form_state->getValue('charity_id'))
      ->set('username', $form_state->getValue('username'))
      ->set('password', $form_state->getValue('password'))
      ->save();
  }

}
