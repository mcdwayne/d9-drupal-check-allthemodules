<?php

namespace Drupal\adva\Form;

use Drupal\adva\Plugin\adva\Manager\AccessConsumerManagerInterface;
use Drupal\adva\Plugin\adva\Manager\AccessProviderManagerInterface;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Advanced Access Control Settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Access Consumer being updated.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Access Consumer manager Service.
   *
   * @var \Drupal\adva\Plugin\adva\Manager\AccessConsumerManagerInterface
   */
  protected $accessConsumerManager;

  /**
   * Access Consumer manager Service.
   *
   * @var \Drupal\adva\Plugin\adva\Manager\AccessProviderManagerInterface
   */
  protected $accessProviderManager;

  /**
   * Create an instance of the Form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   Current Entity Type manager.
   * @param \Drupal\adva\Plugin\adva\Manager\AccessConsumerManagerInterface $consumer_manager
   *   Access Consumer Manager.
   * @param \Drupal\adva\Plugin\adva\Manager\AccessProviderManagerInterface $provider_manager
   *   Access Provider Manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, AccessConsumerManagerInterface $consumer_manager, AccessProviderManagerInterface $provider_manager) {
    $this->entityTypeManager = $entity_manager;
    $this->accessConsumerManager = $consumer_manager;
    $this->accessProviderManager = $provider_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.adva.consumer'),
      $container->get('plugin.manager.adva.provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ["adva.settings"];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adva_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $consumers = $this->accessConsumerManager->getConsumers();

    foreach ($consumers as $consumer_id => $consumer) {
      $entityType = $this->entityTypeManager->getDefinition($consumer->getEntityTypeId());
      $context = [
        "%entity_type" => $entityType->getLabel(),
      ];
      $form["consumers"][$consumer_id] = [
        "#type" => "details",
        "#title" => $this->t("Configure Access For %entity_type", $context),
        "#open" => TRUE,
      ];

      $availableProviders = $this->accessProviderManager->getAvailableProvidersForEntityType($entityType);
      $providers = $consumer->getAccessProviders();
      $form["consumers"][$consumer_id]["providers"] = [
        "#type" => "checkboxes",
        "#title" => $this->t("Enabled Types"),
        "#description" => $this->t("Which Access providers should be enabled for %entity_type entities?", $context),
        "#parents" => ["consumers", $consumer_id, "providers"],
      ];

      $provider_options = [];
      $default_options = [];

      $form["consumers"][$consumer_id]["config"] = [
        "#type" => "item",
        "#parents" => ["consumers", $consumer_id, "config"],
      ];
      $form["consumers"][$consumer_id]["config"]["providers"] = [
        "#type" => "item",
        "#parents" => ["consumers", $consumer_id, "config", "providers"],
      ];

      // Construct ui to configure providers for the given consumer.
      foreach ($availableProviders as $provider_id => $provider_definition) {
        $provider_options[$provider_id] = $provider_definition["label"];
        if (array_key_exists($provider_id, $providers)) {
          $default_options[] = $provider_id;
          $provider = $providers[$provider_id];
          $form["consumers"][$consumer_id]["config"]["providers"][$provider_id] = [
            "#type" => "details",
            "#title" => $provider_definition["label"],
            "#parents" => [
              "consumers",
              $consumer_id,
              "config",
              "providers",
              $provider_id,
            ],
            "#states" => [
              "visible" => [
                '[name="consumers[' . $consumer_id . '][providers][' . $provider_id . ']"' => ["checked" => TRUE],
              ],
            ],
          ];
          $subform_state = SubformState::createForSubform($form["consumers"][$consumer_id]["config"]["providers"][$provider_id], $form, $form_state);
          $form["consumers"][$consumer_id]["config"]["providers"][$provider_id] = $provider->buildConfigForm($form["consumers"][$consumer_id]["config"]["providers"][$provider_id], $subform_state);
        }
      }
      $form["consumers"][$consumer_id]["providers"]["#options"] = $provider_options;
      $form["consumers"][$consumer_id]["providers"]["#default_value"] = $default_options;

      // If there are no supported providers for the consumer, display message.
      if (!count($availableProviders)) {
        $form["consumers"][$consumer_id]["#open"] = FALSE;
        $form["consumers"][$consumer_id]["providers"]["#access"] = FALSE;
        $form["consumers"][$consumer_id]["message"] = [
          "#type" => "item",
          "#title" => $this->t("Enabled Types"),
          "#markup" => $this->t("There are no available Access Providers for <em>%entity_type</em> entites.", $context),
        ];
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    $form['#attached']['library'] = ['adva/form.admin'];

    $form['helpers'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Provider Details'),
    ];

    foreach ($this->accessProviderManager->getDefinitions() as $definition_id => $definition) {
      $provider_class = $this->accessProviderManager->getDefinitionClass($definition_id, $definition);
      $form['helpers'][$definition_id] = [
        '#type' => 'details',
        '#title' => $definition['label'],
        'message' => [
          '#type' => 'item',
          '#markup' => $provider_class::getHelperMessage($definition),
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $consumers = $this->accessConsumerManager->getConsumers();

    // Run validation of consumers and providers.
    foreach ($consumers as $consumer_id => $consumer) {
      $data = $values["consumers"][$consumer_id] ?: [];
      $providerIds = $data["providers"];
      $consumer->setAccessProviderIds(array_filter($providerIds));
      $providers = $consumer->getAccessProviders();

      foreach ($providerIds as $provider_id) {
        if (isset($form["consumers"][$consumer_id]["config"]["providers"][$provider_id])) {
          $subform_state = SubformState::createForSubform($form["consumers"][$consumer_id]["config"]["providers"][$provider_id], $form, $form_state);
          $providers[$provider_id]->validateConfigForm($form["consumers"][$consumer_id]["config"]["providers"][$provider_id], $subform_state);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $consumers = $this->accessConsumerManager->getConsumers();

    // Update per comsumer config.
    foreach ($consumers as $consumer_id => $consumer) {
      $data = $values["consumers"][$consumer_id] ?: [];
      $providerIds = $data["providers"];
      $consumer->setAccessProviderIds(array_filter($providerIds));
      $providers = $consumer->getAccessProviders();

      foreach ($providerIds as $provider_id) {
        if (isset($form["consumers"][$consumer_id]["config"]["providers"][$provider_id])) {
          $subform_state = SubformState::createForSubform($form["consumers"][$consumer_id]["config"]["providers"][$provider_id], $form, $form_state);
          $providers[$provider_id]->submitConfigForm($form["consumers"][$consumer_id]["config"]["providers"][$provider_id], $subform_state);
        }
      }
    }

    // Save the consumer configs. We don't have any specific config right now.
    $this->accessConsumerManager->saveConsumers();

    drupal_set_message($this->t("Advanced Access configuration updated."));
  }

}
