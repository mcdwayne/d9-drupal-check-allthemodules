<?php

namespace Drupal\klaviyo_subscription\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Database;

/**
 * Class AddForm.
 *
 * @package Drupal\klaviyo_subscription\Form
 */
class AddForm extends ConfigFormBase {

  protected $transcoder;
  protected $keyRepo;

  /**
   * AddForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory for parent.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'klaviyo_subscription.config'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kl_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    module_load_include('inc', 'klaviyo_subscription', 'includes/klaviyo_subscription');	
	$kl_lists = klaviyo_subscription_get_klaviyo_list();
	
	$klid = \Drupal::request()->query->get('klid');
	if($klid) {
		$data = klaviyo_subscription_single_list($klid);
	}

	$form['kl_title'] = [
	  '#type' => 'textfield',
	  '#title' => $this->t('List Title'),
	  '#size' => 60,
	  '#maxlength' => 128,
	  '#required' => TRUE,
	  '#default_value' => isset($data->kl_title) ? $data->kl_title : NULL
	];
	
    $form['kl_list_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Klaviyo Lists'),
      '#options' => $kl_lists,
      '#default_value' => isset($data->klaviyo_id) ? $data->klaviyo_id : NULL,
    ];
	
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
	$conn = Database::getConnection();
	db_merge('klaviyo_lists')
		  ->key(array('klaviyo_id' => $form_state->getValue('kl_list_id')))
		  ->fields(array(
			  'kl_title' => $form_state->getValue('kl_title'),
		  ))
	->execute();
  }

}
