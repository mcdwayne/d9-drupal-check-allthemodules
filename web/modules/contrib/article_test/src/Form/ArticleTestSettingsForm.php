<?php

namespace Drupal\role_paywall_article_test\Form;

use Drupal\flag\FlagServiceInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RolePaywallSettingsForm.
 */
class ArticleTestSettingsForm extends ConfigFormBase {

  /**
   * Stores locally the injected manager.
   *
   * @var FlagServiceInterface
   */
  private $flagService;

  /**
   * {@inheritdoc}
   */
  public function __construct(FlagServiceInterface $flagService) {
    $this->flagService = $flagService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flag')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'role_paywall_article_test_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'role_paywall_article_test.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->config('role_paywall_article_test.settings');

    $flags = $this->flagService->getAllFlags();

    $options = [];
    foreach ($flags as $flag_id => $flag) {
      $options[$flag_id] = $flag->label;
    }
    $form['access_flag'] = [
      '#type' => 'select',
      '#title' => $this->t('Flag used article test data.'),
      '#options' => $options,
      '#default_value' => $configuration->get('access_flag'),
    ];
    $form['blocking_period_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Subsequent 1-article-test wait period (in days)'),
      '#default_value' => $configuration->get('blocking_period_days'),
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
    $this->config('role_paywall_article_test.settings')
      ->set('access_flag', $form_state->getValue('access_flag'))
      ->set('blocking_period_days', $form_state->getValue('blocking_period_days'))
      ->save();
    parent::submitForm($form, $form_state);

  }

}
