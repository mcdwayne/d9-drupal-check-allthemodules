<?php

/**
 * @file
 * Contains \Drupal\usajobs\Plugin\Block\UsaJobsBlock.
 */

namespace Drupal\usajobs\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides a 'USAJobs' block.
 *
 * @Block(
 *   id = "usajobs_block",
 *   admin_label = @Translation("USAJobs Listing"),
 * )
 */
class USAJobsBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $default_config = \Drupal::config('usajobs.settings');
    $config = $this->getConfiguration();

    $form['usajobs_organization_ids'] = array (
      '#type' => 'textfield',
      '#title' => t('Organization ID'),
      '#default_value' => isset($config['usajobs_organization_ids']) ? $config['usajobs_organization_ids'] : $default_config->get('usajobs_organization_ids'),
      '#required' => TRUE,
      '#description' => t('Specifies which federal, state, or local agency to use as a filter.<br /><br /> For federal agencies, the ID is based on <a href="https://schemas.usajobs.gov/Enumerations/AgencySubElement.xml" target="_blank">USAJobs\' agency schema</a>. Two letter codes are used to span entire departments, while four letter codes are generally used for independent agencies or agencies within a department.<br /><br />
      For state and local agencies, a sample of the format follows. <br /><br />
      State of Virginia <strong>US-VA</strong><br />
      State of Virginia Department of Taxation <strong>US-VA:DEPT-TAX</strong><br />
      Fairfax County, VA <strong>US-VA:COUNTY-FAIRFAX</strong><br />
      Fairfax County Sheriff <strong>US-VA:COUNTY-FAIRFAX:SHERIFF</strong><br />
      City of Fairfax, VA <strong>US-VA:COUNTY-FAIRFAX:CITY-FAIRFAX</strong><br />
      '),
    );

    $form['usajobs_size'] = array(
      '#type' => 'textfield',
      '#title' => t('Maximum Number of Jobs to Display'),
      '#default_value' => isset($config['usajobs_size']) ? $config['usajobs_size'] : $default_config->get('usajobs_size'),
      '#size' => 5,
      '#maxlength' => 3,
      '#required' => TRUE,
      '#description' => t('Specifies how many results are displayed (up to 100).'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $size = $form_state->getValue('usajobs_size');
    if ($size !== '' && (!is_numeric($size) || intval($size) != $size || $size <= 0 || $size > 100)) {
      $form_state->setErrorByName('usajobs_size', $this->t('Please enter an integer number between 1 and 100.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $this->configuration['usajobs_organization_ids'] = $form_state->getValue('usajobs_organization_ids');
    $this->configuration['usajobs_size'] = $form_state->getValue('usajobs_size');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Request USAJobs Search API.
    $client = \Drupal::httpClient();
    $request_url = 'https://api.usa.gov/jobs/search.json';
    $query = array (
      'organization_ids' => $this->configuration['usajobs_organization_ids'],
      'size' => $this->configuration['usajobs_size'],
    );

    try {
      $response = $client->get($request_url, ['query' => $query]);
      $results = json_decode($response->getBody());
    }
    catch(RequestException $e) {
      watchdog_exception('usajobs', $e->getMessage());
    }

    //Prepare mockup for the output.
    $markup ='';
    foreach ($results as $result) {
      $job_item = [
        '#theme' => 'usajobs_item',
        '#items' => $result,
      ];

      $markup .= drupal_render($job_item);
     }

    $markup = '<div id="usajobs">' . $markup . '</div>';

    //Build block content.
    $build = [
      '#markup' => $markup,
      '#attached' => [
         'library' => [
           'usajobs/drupal.usajobs',
         ],
      ],
    ];

    return $build;
  }

}
