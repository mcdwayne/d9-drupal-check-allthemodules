<?php

namespace Drupal\micro_path\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Site type' condition.
 *
 * @Condition(
 *   id = "site_type",
 *   label = @Translation("Node's site type"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"), required = FALSE)
 *   }
 * )
 */
class SiteType extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The micro site negotiator..
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The entity bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Creates a new SiteType instance.
   *
   * @param \Drupal\micro_site\SiteNegotiatorInterface $negotiator
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity bundle info service.
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(SiteNegotiatorInterface $negotiator, EntityTypeBundleInfoInterface $entity_type_bundle_info, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->negotiator = $negotiator;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('micro_site.negotiator'),
      $container->get('entity_type.bundle.info'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['site_type'] = [
      '#title' => $this->t("Node's site type"),
      '#type' => 'checkboxes',
      '#options' => $this->getSiteTypes(),
      '#default_value' => $this->configuration['site_type'] ?: [],
      '#description' => $this->t('Leave empty to select all. This condition apply if the content is attached on a site with the type selected above.'),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['site_type'] = array_filter($form_state->getValue('site_type'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if ($site_types = $this->configuration['site_type']) {
      $list_site_types = $this->getSiteTypes();
      $list= [];
      foreach ($site_types as $id => $site) {
        $list[] = $list_site_types[$id];
      }
      $site_types_label = implode(', ', $list);
      $replace = ['@site_type' => $site_types_label];
      return $this->isNegated()
        ? $this->t("The node's site type is not @site_type", $replace)
        : $this->t("The node's site type is @site_type", $replace);
    }

    // If no site type is selected it means the content should not be listed.
    return $this->isNegated() ? $this->t("The node has not a site of any type.") : $this->t("The node has a site of any type.");
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $site_types = $this->configuration['site_type'];
    if (empty($site_types) && !$this->isNegated()) {
      return TRUE;
    }
    $node = $this->getContextValue('node');
    if (!$node instanceof NodeInterface) {
      return $this->isNegated();
    }
    $site = $node->get('site_id')->entity;
    if(!$site instanceof SiteInterface) {
      $site = $this->negotiator->getActiveSite();
      // This condition always fails if the no micro site active..
      if (!$site instanceof SiteInterface) {
        return FALSE;
      }
    }

    // Check the active site type.
    /** @var \Drupal\micro_site\Entity\SiteTypeInterface $site_type */
    $active_site_type = $site->type->entity;
    $active_site_type_id = $active_site_type->id();
    if ($site_types) {
      return in_array($active_site_type_id, $site_types) xor $this->isNegated();
    }

    // No site type found on the node. Let's the negate option allow or not the visibility.
    return $this->isNegated();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['site_type' => ''] + parent::defaultConfiguration();
  }

  /**
   * Retrieves the site types.
   *
   * @return string[]
   *   The list of site types, keyed by their machine name.
   */
  public function getSiteTypes() {
    $options = [];
    $entity_type_bundles = $this->entityTypeBundleInfo->getBundleInfo('site');
    foreach ($entity_type_bundles as $key => $entity_type_bundle) {
      $options[$key] = $entity_type_bundle['label'];
    }
    return $options;
  }
}
