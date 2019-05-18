<?php

namespace Drupal\entity_submit_redirect\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form.
 *
 * @todo
 *   Make ability to select single redirection for one entity type
 *   Make ability to make single redirection for all the entitites
 */
class EntitySubmitRedirectConfigForm extends ConfigFormBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  private $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManager $entityTypeManager, EntityTypeBundleInfo $entityTypeBundleInfo) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;

    parent::__construct($configFactory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['entity_submit_redirect.settings'];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'msh_redirect_after_submit_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $formState = NULL) {
    $config = $this->config('entity_submit_redirect.settings');

    $form['global_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Global settings'),
    ];

    $options = [
      'front' => $this->t('Front pages'),
      'backoffice' => $this->t('Backoffice pages'),
    ];

    $defaultValue = $config->get('global.use_on');

    $form['global_settings']['use_on'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Use on'),
      '#multiple' => TRUE,
      '#options' => $options,
      '#default_value' => $defaultValue ? $defaultValue : array_keys($options),
    ];

    $form['entity_types'] = [
      '#type' => 'vertical_tabs',
    ];

    // Next block made only for a sorting by the label.
    foreach ($this->entityTypeManager->getDefinitions() as $entityTypeDefinition) {
      // We are looking only for content entities.
      if (!$entityTypeDefinition instanceof ContentEntityType) {
        continue;
      }
      $definitions[(string) $entityTypeDefinition->getLabel()] = $entityTypeDefinition;
    }
    ksort($definitions);

    $form['configuration'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    /** @var \Drupal\Core\Entity\ContentEntityType $definition */
    foreach ($definitions as $definition) {

      $id = $definition->id();
      $form['configuration'][$id] = [
        '#type' => 'details',
        '#title' => $definition->getLabel(),
        '#group' => 'entity_types',
      ];

      $bundleInfo = $this->entityTypeBundleInfo->getBundleInfo($id);

      $defaultKey = '_default';
      $form['configuration'][$id][$defaultKey] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Default value'),
        '#description' => t('Every bundle of current entity type will be redirected to the default path if no other path is specified.'),
      ];
      $form['configuration'][$id][$defaultKey]['active'] = [
        '#type' => 'checkbox',
        '#title' => t('Is active'),
        '#default_value' => (bool) $config->get("$id.$defaultKey.path"),
      ];

      $form['configuration'][$id][$defaultKey]['path'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Redirection path'),
        '#default_value' => $config->get("$id.$defaultKey.path"),
        '#states' => [
          'invisible' => [
            'input[name="configuration' . "[{$id}][$defaultKey][active]" . '"]' => ['checked' => FALSE],
          ],
        ],
      ];

      if (!empty($bundleInfo)) {
        foreach ($bundleInfo as $bundleId => $bundleInfo) {
          $form['configuration'][$id][$bundleId]['active'] = [
            '#type' => 'checkbox',
            '#title' => $bundleInfo['label'],
            '#default_value' => (bool) $config->get("$id.$bundleId.path"),
          ];

          $form['configuration'][$id][$bundleId]['path'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Redirection path'),
            '#default_value' => $config->get("$id.$bundleId.path"),
            '#states' => [
              'invisible' => [
                'input[name="configuration' . "[{$id}][$bundleId][active]" . '"]' => ['checked' => FALSE],
              ],
            ],
          ];
        }
      }
    }

    return parent::buildForm($form, $formState);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    $config = $this->config('entity_submit_redirect.settings');
    foreach ($formState->getValue('configuration') as $entityTypeId => $bundlesInfo) {
      foreach ($bundlesInfo as $bundleId => $values) {
        // If this point is selected is active:
        if ($values['active']) {
          $config->set("$entityTypeId.$bundleId.path", $values['path']);
        }
        else {
          $config->clear("$entityTypeId.$bundleId.path");
        }
      }
    }

    $useOn = $formState->getValue('use_on');
    $config->set('global.use_on', array_keys($useOn));

    $config->save();

    parent::submitForm($form, $formState);
  }

}
