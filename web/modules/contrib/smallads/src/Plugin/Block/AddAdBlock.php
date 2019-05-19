<?php

namespace Drupal\smallads\Plugin\Block;

use Drupal\smallads\Entity\SmalladType;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an block to start creating a smallad.
 *
 * @Block(
 *   id = "add_ad_block",
 *   admin_label = @Translation("Add ad"),
 *   category = @Translation("Smallads")
 * )
 */
class AddAdBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'adtype' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $types = ['' => $this->t('User selects type')];
    foreach (SmalladType::loadMultiple() as $id => $adType) {
      $types[$id] = $adType->label();
    }
    $form['adtype'] = [
      '#title' => $this->t('Ad Type'),
      '#description' => $this->t('This should agree with the block title.'),
      '#type' => 'radios',
      '#options' => $types,
      '#default_value' => $this->configuration['adtype'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    foreach ($form_state->getValues() as $key => $val) {
      $this->configuration[$key] = $val;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    // Only grant access to users with the 'access news feeds' permission.
    return AccessResult::allowedIfHasPermission($account, 'post smallad');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->formBuilder
      ->getForm('Drupal\smallads\Form\PreAddForm', $this->configuration['adtype']);
  }

}
