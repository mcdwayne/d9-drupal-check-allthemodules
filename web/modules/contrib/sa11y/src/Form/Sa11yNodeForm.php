<?php

namespace Drupal\sa11y\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\sa11y\Sa11yInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration form for on-demand node checks.
 */
class Sa11yNodeForm extends ConfirmFormBase {

  /**
   * The Sa11y service.
   *
   * @var \Drupal\sa11y\Sa11y
   */
  protected $sa11y;

  /**
   * The settings data.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $sa11ySettings;

  /**
   * A loaded node object.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Constructs a new Sa11yNodeForm.
   *
   * @param \Drupal\sa11y\Sa11yInterface $sa11y
   *   The Sa11y service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(Sa11yInterface $sa11y, ConfigFactoryInterface $config_factory) {
    $this->sa11y = $sa11y;
    $this->sa11ySettings = $config_factory->get('sa11y.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sa11y.service'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sa11y_node';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t("A notification will be shown when the report is ready.");
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Run accessibility check on this page?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.node.canonical', ['node' => $this->node->id()]);
  }

  /**
   * {@inheritdoc}
   *
   * Ensure settings and no pending reports.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $options = [
      'parameters' => [
        'rules' => $form_state->getValue('rules'),
        'include' => $form_state->getValue('include'),
        'exclude' => $form_state->getValue('exclude'),
      ],
    ];

    if (!$this->sa11y->checkRequirements()) {
      $form_state->setError($form, $this->t('You need to set your API key in the @settings.', [
        '@settings' => Link::createFromRoute($this->t('settings page'), 'sa11y.admin_settings')
          ->toString(),
      ]));
      return;
    }

    // Attempt to create a new report.
    if (!$this->sa11y->createReport($this->node->toUrl()
      ->setAbsolute()
      ->toString(), $options)) {
      $form_state->setErrorByName('', $this->t('A new report could not be created. A report might already be pending.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->sa11y->processPending();
    drupal_set_message($this->t('Accessibility Report will be available shortly.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL) {
    $this->node = $node;
    $form = parent::buildForm($form, $form_state);

    $form['info'] = [
      '#markup' => '',
    ];

    $form['rules'] = [
      '#title' => $this->t('Select which rules to use'),
      '#type' => 'checkboxes',
      '#description' => $this->t('Select which rules to apply to your scans. Select none to use all rules.'),
      '#options' => [
        'wcag2a' => $this->t('WCAG 2.0 Level A'),
        'wcag2aa' => $this->t('WCAG 2.0 Level AA'),
        'section508' => $this->t('Section 508'),
        'best-practice' => $this->t('Best Practice'),
        'experimental' => $this->t('Cutting-edge techniques'),
      ],
      '#default_value' => $this->sa11ySettings->get('rules'),
    ];

    $form['include'] = [
      '#title' => $this->t('Inclusions'),
      '#descriptions' => $this->t('A list of css selectors to include in the check, each on a new line.'),
      '#type' => 'textarea',
      '#size' => 3,
      '#default_value' => $this->sa11ySettings->get('include'),
    ];

    $form['exclude'] = [
      '#title' => $this->t('Exclusions'),
      '#descriptions' => $this->t('A list of css selectors to exclude in the check, each on a new line.'),
      '#type' => 'textarea',
      '#size' => 3,
      '#default_value' => $this->sa11ySettings->get('exclude'),
    ];

    return $form;
  }

}
