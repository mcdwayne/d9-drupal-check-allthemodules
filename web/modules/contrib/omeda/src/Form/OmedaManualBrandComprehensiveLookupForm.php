<?php

namespace Drupal\omeda\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\omeda\Omeda;
use Drupal\Core\State\State;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class OmedaManualBrandComprehensiveLookupForm.
 *
 * @package Drupal\omeda\Form
 */
class OmedaManualBrandComprehensiveLookupForm extends FormBase {

  /**
   * The Omeda  service.
   *
   * @var \Drupal\omeda\Omeda
   */
  protected $omeda;

  /**
   * The Drupal State service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Constructs a \Drupal\omeda\Form\OmedaSubscriptionsManagementForm object.
   *
   * @param \Drupal\omeda\Omeda $omeda
   *   The Omeda service.
   * @param \Drupal\Core\State\State $state
   *   The Drupal State service.
   */
  public function __construct(Omeda $omeda, State $state) {
    $this->omeda = $omeda;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('omeda'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'omeda_manual_brand_comprehensive_lookup_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<div>' . $this->t('Brand comprehensive lookup runs automatically once a day. Click the button below to run it immediately.') . '</div>',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run Brand Comprehensive Lookup'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    try {
      $brand_lookup = $this->omeda->brandComprehensiveLookup();
      $this->state->set('omeda.brand_lookup', $brand_lookup);
      $this->messenger()->addMessage($this->t('Brand Comprehensive Lookup updated.'));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Brand Comprehensive Lookup failed.'));
    }
  }

}
