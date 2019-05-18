<?php

namespace Drupal\feature_toggle\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\feature_toggle\FeatureManagerInterface;
use Drupal\feature_toggle\FeatureStatusInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FeatureToggleForm.
 */
class FeatureToggleForm extends FormBase {

  /**
   * The Feature Toggle Status service.
   *
   * @var \Drupal\feature_toggle\FeatureStatusInterface
   */
  protected $featureStatus;

  /**
   * The Feature Manager service.
   *
   * @var \Drupal\feature_toggle\FeatureManagerInterface
   */
  protected $featureManager;

  /**
   * Constructs a new FeatureToggleForm object.
   */
  public function __construct(FeatureStatusInterface $feature_status, FeatureManagerInterface $feature_manager) {
    $this->featureStatus = $feature_status;
    $this->featureManager = $feature_manager;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('feature_toggle.feature_status'),
      $container->get('feature_toggle.feature_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'feature_toggle_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];

    $form['filters']['text'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter features'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by name or description'),
      '#description' => $this->t('Enter a part of the feature name'),
      '#attributes' => [
        'class' => ['table-filter-text'],
        'data-table' => '#feature-toggle-form',
        'autocomplete' => 'off',
      ],
    ];

    $form['features'] = [];
    $form['features_status'] = ['#tree' => TRUE];

    foreach ($this->featureManager->getFeatures() as $feature) {
      $form['features'][$feature->name()]['#label'] = $feature->label();

      $form['status'][$feature->name()] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Toggle @label feature', ['@label' => $feature->label()]),
        '#title_display' => 'invisible',
        '#default_value' => $this->featureStatus->getStatus($feature->name()),
      ];
    }

    $form['#attached']['library'][] = 'feature_toggle/filter';
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->featureManager->getFeatures() as $feature) {
      if ($form_state->getValue($feature->name()) != $this->featureStatus->getStatus($feature->name())) {
        $this->featureStatus->setStatus($feature, $form_state->getValue($feature->name()));
      }
    }

    drupal_set_message(t('Feature status saved successfully'));
  }

  /**
   * Custom form access checker based on two permissions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('administer feature_toggle') || $account->hasPermission('modify feature_toggle status'));
  }

}
