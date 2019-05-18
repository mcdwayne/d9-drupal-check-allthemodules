<?php

namespace Drupal\indeed_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Presents the module settings form.
 */
class IndeedSearchSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'indeed_search_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['indeed_search.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('indeed_search.settings');
	
    $form['indeed_pubid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Indeed Publisher ID'),
      '#default_value' => $config->get('indeed_pubid','7681429865724784'),
      '#required' => TRUE,
    ];
  
    $form['indeed_q'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Query'),
      '#default_value' => $config->get('indeed_q'),
      '#required' => TRUE,
    ];
  
    $form['indeed_l'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Location'),
      '#default_value' => $config->get('indeed_l'),
    ];
  
    $form['indeed_radius'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Radius'),
      '#default_value' => $config->get('indeed_radius'),
      '#rules' => array('rule' => 'numeric', 'error' => 'Must be a number'),
      '#required' => TRUE,
    ];

    $form['indeed_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Limit'),
      '#default_value' => $config->get('indeed_limit'),
      '#required' => TRUE,
    ];
  
    $form['indeed_sort'] = [
      '#type' => 'select',
      '#title' => 'Sort by',
      '#options' => ['relevance' => t('Relevance'),
          'date' => $this->t('Date'),
      ],
      '#default_value' => $config->get('indeed_sort'),
    ];
  
    $form['indeed_country'] = [
      '#type' => 'select',
      '#title' => 'Country',
      '#options' => ['us' => $this->t('United States'),
                        'ar' => $this->t('Argentina'),
                        'au' => $this->t('Australia'),
                        'at' => $this->t('Austria'),
                        'bh' => $this->t('Bahrain'),
                        'be' => $this->t('Belgium'),
                        'br' => $this->t('Brazil'),
                        'ca' => $this->t('Canada'),
                        'cl' => $this->t('Chile'),
                        'cn' => $this->t('China'),
                        'co' => $this->t('Colombia'),
                        'cz' => $this->t('Czech Republic'),
                        'dk' => $this->t('Denmark'),
                        'fi' => $this->t('Finland'),
                        'fr' => $this->t('France'),
                        'de' => $this->t('Germany'),
                        'gr' => $this->t('Greece'),
                        'hk' => $this->t('Hong Kong'),
                        'hu' => $this->t('Hungary'),
                        'in' => $this->t('India'),
                        'id' => $this->t('Indonesia'),
                        'ie' => $this->t('Ireland'),
                        'il' => $this->t('Israel'),
                        'it' => $this->t('Italy'),
                        'jp' => $this->t('Japan'),
                        'kr' => $this->t('Korea'),
                        'kw' => $this->t('Kuwait'),
                        'lu' => $this->t('Luxembourg'),
                        'my' => $this->t('Malaysia'),
                        'mx' => $this->t('Mexico'),
                        'nl' => $this->t('Netherlands'),
                        'nz' => $this->t('New Zealand'),
                        'no' => $this->t('Norway'),
                        'om' => $this->t('Oman'),
                        'pk' => $this->t('Pakistan'),
                        'pe' => $this->t('Peru'),
                        'ph' => $this->t('Philippines'),
                        'pl' => $this->t('Poland'),
                        'pt' => $this->t('Portugal'),
                        'qa' => $this->t('Qatar'),
                        'ro' => $this->t('Romania'),
                        'ru' => $this->t('Russia'),
                        'sa' => $this->t('Saudi Arabia'),
                        'sg' => $this->t('Singapore'),
                        'za' => $this->t('South Africa'),
                        'es' => $this->t('Spain'),
                        'se' => $this->t('Sweden'),
                        'ch' => $this->t('Switzerland'),
                        'tw' => $this->t('Taiwan'),
                        'tr' => $this->t('Turkey'),
                        'ae' => $this->t('United Arab Emirates'),
                        'gb' => $this->t('United Kingdom'),
                        've' => $this->t('Venezuela'),
      ],
      '#default_value' => $config->get('indeed_country'),
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
 
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('indeed_search.settings')
      ->set('indeed_pubid', $form_state->getValue('indeed_pubid'))
      ->set('indeed_q', $form_state->getValue('indeed_q'))
      ->set('indeed_l', $form_state->getValue('indeed_l'))
      ->set('indeed_radius', $form_state->getValue('indeed_radius'))
	  ->set('indeed_limit', $form_state->getValue('indeed_limit'))
	  ->set('indeed_sort', $form_state->getValue('indeed_sort'))
	  ->set('indeed_country', $form_state->getValue('indeed_country'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}