<?php

namespace Drupal\feature_toggle\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\feature_toggle\FeatureManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FeatureDelete.
 */
class FeatureDeleteForm extends ConfirmFormBase {

  /**
   * The feature manager.
   *
   * @var \Drupal\feature_toggle\FeatureManagerInterface
   */
  protected $featureManager;

  /**
   * The feature to delete.
   *
   * @var \Drupal\feature_toggle\FeatureInterface
   */
  protected $feature;

  /**
   * Constructs a new FeatureDeleteForm object.
   */
  public function __construct(FeatureManagerInterface $feature_status) {
    $this->featureManager = $feature_status;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('feature_toggle.feature_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'feature_toggle_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the feature %label?', ['%label' => $this->feature->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('feature_toggle.feature_toggle_form');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $feature_name = NULL) {
    $this->feature = $this->featureManager->getFeature($feature_name);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->featureManager->deleteFeature($this->feature->name());

    drupal_set_message($this->t('Feature <strong>@label</strong> deleted successfully.', ['@label' => $this->feature->label()]));
    $form_state->setRedirect('feature_toggle.feature_toggle_form');
  }

  /**
   * Custom form access checker based on permissions and existing feature name.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param string $feature_name
   *   The feature name to delete.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function access(AccountInterface $account, $feature_name) {
    return AccessResult::allowedIf($account->hasPermission('administer feature_toggle') && $this->featureManager->featureExists($feature_name));
  }

}
