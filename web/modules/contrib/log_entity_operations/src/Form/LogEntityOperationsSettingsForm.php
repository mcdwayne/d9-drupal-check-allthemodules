<?php

namespace Drupal\log_entity_operations\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LogEntityOperationsSettingsForm.
 */
class LogEntityOperationsSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  const CONFIG_NAME = 'log_entity_operations.settings';

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * Entity Type Bundle Info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  private $entityTypeBundleInfo;

  /**
   * Constructs a new LogEntityOperationsSettingsForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type Manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   Entity Type Bundle Info.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'log_entity_operations_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config(self::CONFIG_NAME);

    $form['log_diff'] = [
      '#type' => 'select',
      '#title' => $this->t('Log diff of changes'),
      '#required' => TRUE,
      '#default_value' => (int) $config->get('log_diff'),
      '#options' => [
        1 => $this->t('Yes'),
        0 => $this->t('No'),
      ],
    ];

    $bundles = [];

    foreach (array_keys($this->entityTypeManager->getDefinitions()) as $entity_type) {
      foreach (array_keys($this->entityTypeBundleInfo->getBundleInfo($entity_type)) as $bundle) {
        if ($entity_type === $bundle) {
          continue;
        }

        $bundles[$entity_type . '.' . $bundle] = $entity_type . '.' . $bundle;
      }
    }

    $form['enabled_for'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Log operations for Entity.Bundles'),
      '#default_value' => (array) $config->get('enabled_for'),
      '#options' => $bundles,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $enabled_for = array_values(array_filter($form_state->getValue('enabled_for')));

    $config = $this->config(self::CONFIG_NAME);
    $config->set('log_diff', (bool) $form_state->getValue('log_diff'));
    $config->set('enabled_for', $enabled_for);
    $config->save();

    return parent::submitForm($form, $form_state);
  }


}
