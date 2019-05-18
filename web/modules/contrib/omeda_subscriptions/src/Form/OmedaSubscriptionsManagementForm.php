<?php

namespace Drupal\omeda_subscriptions\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\omeda_subscriptions\OmedaSubscriptions;
use Drupal\Core\State\State;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class OmedaSubscriptionsManagementForm.
 *
 * @package Drupal\omeda_subscriptions\Form
 */
class OmedaSubscriptionsManagementForm extends FormBase {

  /**
   * The Omeda Subscriptions service.
   *
   * @var \Drupal\omeda_subscriptions\OmedaSubscriptions
   */
  protected $omedaSubscriptions;

  /**
   * The Drupal State service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Constructs a \Drupal\omeda\Form\OmedaSubscriptionsManagementForm object.
   *
   * @param \Drupal\omeda_subscriptions\OmedaSubscriptions $omeda_subscriptions
   *   The Omeda Subscriptions service.
   * @param \Drupal\Core\State\State $state
   *   The Drupal State service.
   */
  public function __construct(OmedaSubscriptions $omeda_subscriptions, State $state) {
    $this->omedaSubscriptions = $omeda_subscriptions;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('omeda_subscriptions'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'omeda_subscriptions_management_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    try {
      $opt_lookup = $this->omedaSubscriptions->optLookup($this->currentUser()->getEmail());
      $config = $this->config('omeda_subscriptions.settings');
      $available_subscriptions = $config->get('enabled_subscriptions');
      $brand_lookup = $this->state->get('omeda.brand_lookup', '');

      if ($available_subscriptions && $brand_lookup) {

        $deployments = [];

        foreach ($brand_lookup['DeploymentTypes'] as $deployment_type) {

          if (in_array($deployment_type['Id'], $available_subscriptions)) {
            $deployments[$deployment_type['Id']] = $deployment_type['Description'];
          }
        }
        $default_values = [];

        foreach ($deployments as $key => $deployment) {

          if (isset($opt_lookup[$key])) {
            if ($opt_lookup[$key] === "IN") {
              $default_values[$key] = $key;
            }
            elseif ($opt_lookup[$key] === "OUT") {
              $deployments[$key] = $deployment . ' (opted out)';
            }
          }
        }
        $form['info'] = [
          '#type' => 'markup',
          '#markup' => $this->t('Manage your available subscriptions below.'),
        ];
        $form['omeda_subscriptions'] = [
          '#type' => 'checkboxes',
          '#options' => $deployments,
          '#title' => $this->t('Subscriptions you are opted into'),
          '#default_value' => $default_values,
        ];
        $form['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Submit'),
        ];
      }

      else {
        $form['info'] = [
          '#type' => 'markup',
          '#markup' => $this->t('No subscriptions available.'),
        ];
      }

    }

    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Subscription lookup failed, please try again later.'));
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    try {
      $opt_lookup = $this->omedaSubscriptions->optLookup($this->currentUser()->getEmail());
      $opted_in = [];

      if ($opt_lookup) {
        foreach ($opt_lookup as $key => $opt) {

          if ($opt === "IN") {
            $opted_in[] = $key;
          }
        }
      }
      $optins = [];
      $optouts = [];

      foreach ($form_state->getValue('omeda_subscriptions') as $key => $subscription) {

        // Handle opt ins.
        if ($subscription) {
          $optins[] = $key;
        }

        // Handle opt outs.
        elseif (in_array($key, $opted_in)) {
          $optouts[] = $key;
        }
      }
      // Submit opt ins.
      if ($optins) {
        $this->omedaSubscriptions->optInDeploymentTypes($this->currentUser()->getEmail(), $optins, TRUE);
      }
      // Submit opt outs.
      if ($optouts) {
        $this->omedaSubscriptions->optOutDeploymentTypes($this->currentUser()->getEmail(), $optouts);
      }
      $this->messenger()->addMessage($this->t('Subscription settings updated.'));
    }

    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Subscription update failed, please try again later.'));
    }
  }

}
