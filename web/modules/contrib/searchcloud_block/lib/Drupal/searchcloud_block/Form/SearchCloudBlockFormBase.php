<?php

/**
 * @file
 * Contains \Drupal\searchcloud_block\Form\SearchCloudBlockStandardForms.
 */

namespace Drupal\searchcloud_block\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\searchcloud_block\Services\SearchCloudServiceProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SearchCloudBlockFormBase extends ConfigFormBase {

  protected $serviceProvider;
  protected $configFactory;

  /**
   * Class constructor.
   */
  public function __construct(SearchCloudServiceProviderInterface $service_provider, ConfigFactory $config_factory) {
    $this->serviceProvider = $service_provider;
    $this->configFactory   = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('searchcloud_block.serviceProvider'), $container->get('config.factory'));
  }

  /**
   * Generate the standard add/edit form.
   *
   * @todo: preset values for edit form.
   */
  public function getStandardAddEditForm(array &$form, array &$form_state) {
    $form['term']              = array(
      '#type'     => 'textfield',
      '#title'    => t('Searchterm'),
      '#required' => TRUE,
    );
    $form['count']             = array(
      '#type'      => 'textfield',
      '#title'     => t('Count'),
      '#size'      => 6,
      '#maxlength' => 6,
      '#required'  => TRUE,
    );
    $form['actions']['#type']  = 'actions';
    $form['actions']['submit'] = array(
      '#type'        => 'submit',
      '#value'       => $this->t('Save'),
      '#button_type' => 'primary',
    );
  }

  /**
   * Generate a standard validate function.
   */
  public function getStandardAddEditValidation(array &$form, array &$form_state) {
    if (isset($form_state['values']['count']) && !is_numeric($form_state['values']['count'])) {
      $this->setFormError('count', $form_state, t('The count must be a number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'searchcloudblock_standard_forms';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $termobject = $this->serviceProvider->getTerm($form_state['values']['term']);
    $update     = TRUE;

    // This one already exists, so update.
    if (empty($termobject)) {
      $update = FALSE;
      $term   = array(
        'keyword' => $form_state['values']['term'],
        'count'   => $form_state['values']['count'],
      );
    }
    else {
      $term          = (array) $termobject;
      $term['count'] = $form_state['values']['count'];
    }

    $this->saveTerm($term, $update);
  }

  /**
   * Actually save the term in the database.
   */
  protected function saveTerm($term, $update = FALSE) {
    if ($update) {
      db_update('searchcloud_block_count')->fields($term)->condition('keyword', $term['keyword'])->execute();
    }
    else {
      db_insert('searchcloud_block_count')->fields($term)->execute();
    }
  }

}
