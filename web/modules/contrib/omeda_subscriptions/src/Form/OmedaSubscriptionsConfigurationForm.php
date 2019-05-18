<?php

namespace Drupal\omeda_subscriptions\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\State;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class OmedaSubscriptionsConfigurationForm.
 *
 * @package Drupal\omeda_subscriptions\Form
 */
class OmedaSubscriptionsConfigurationForm extends ConfigFormBase {

  /**
   * The Drupal State service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Constructs a OmedaSubscriptionsConfigurationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\State\State $state
   *   The Drupal State service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, State $state) {
    parent::__construct($config_factory);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'omeda_subscriptions_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['omeda_subscriptions.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($brand_lookup = $this->state->get('omeda.brand_lookup')) {

      $products = [];
      foreach ($brand_lookup['Products'] as $product) {
        // Lookup products that are newsletters(2) or email deployments(5).
        if ($product['ProductType'] === 2 || $product['ProductType'] === 5) {
          $products[$product['Id']] = $product['DeploymentTypeId'];
        }
      }
      $deployments = [];
      foreach ($brand_lookup['DeploymentTypes'] as $deployment_type) {
        // Match deployments that are of newsletter or email product types.
        if (in_array($deployment_type['Id'], $products)) {
          $deployments[$deployment_type['Id']] = $deployment_type['Description'];
        }
      }

      $form['#attached']['library'][] = 'omeda_subscriptions/subscription_configuration';
      $form['description'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Select which subscriptions you would like your users to be able to opt in/out of. Below are all of the available subscriptions for your account.'),
      ];
      $form['omeda_subscriptions_wrapper'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Subscriptions Available'),
      ];
      $form['omeda_subscriptions_wrapper']['omeda_check_helper'] = [
        '#type' => 'button',
        '#value' => $this->t('Select All'),
        '#attributes' => [
          'class' => [
            'check-toggle',
          ],
        ],
      ];
      $form['omeda_subscriptions_wrapper']['omeda_subscriptions'] = [
        '#type' => 'checkboxes',
        '#options' => $deployments,
        '#attributes' => [
          'class' => [
            'subscription',
          ],
        ],
        '#default_value' => $this->config('omeda_subscriptions.settings')->get('enabled_subscriptions') ?: [],
      ];

    }
    else {
      $form['no_lookup'] = [
        '#type' => 'markup',
        '#markup' => $this->t('Brand comprehensive lookup needs to be run before you can configure subscriptions. <a href="@url"> Click here to run it manually</a>', [
          '@url' => Url::fromRoute('omeda.manual_brand_comprehensive_lookup')->toString(),
        ]),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('omeda_subscriptions.settings')
      ->set('enabled_subscriptions', $form_state->getValue('omeda_subscriptions'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
