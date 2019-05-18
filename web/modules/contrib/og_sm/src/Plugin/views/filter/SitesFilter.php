<?php

namespace Drupal\og_sm\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\og_sm\SiteManagerInterface;
use Drupal\views\Plugin\views\filter\InOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter handler to filter entities based on a their site.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("og_sm_sites")
 */
class SitesFilter extends InOperator implements ContainerFactoryPluginInterface {

  /**
   * The site manager.
   *
   * @var \Drupal\og_sm\SiteManagerInterface
   */
  protected $siteManager;

  /**
   * Constructs a new SitesFilter instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $pluginId
   *   The plugin_id for the plugin instance.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   * @param \Drupal\og_sm\SiteManagerInterface $siteManager
   *   The language manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The string translation manager.
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, SiteManagerInterface $siteManager, TranslationInterface $stringTranslation) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->siteManager = $siteManager;
    $this->setStringTranslation($stringTranslation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('og_sm.site_manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['manageable_sites']['default'] = FALSE;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $formState) {
    parent::buildOptionsForm($form, $formState);
    $form['manageable_sites'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Limit options to the current user's manageable sites"),
      '#default_value' => $this->options['manageable_sites'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return;
    }

    if ($this->options['manageable_sites']) {
      $sites = $this->siteManager->getUserManageableSites();
    }
    else {
      $sites = $this->siteManager->getAllSites();
    }

    $options = [];
    foreach ($sites as $site) {
      $options[$site->id()] = $site->label();
    }
    asort($options);
    $this->valueOptions = $options;

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (empty($this->value)) {
      return;
    }
    $this->ensureMyTable();
    $field = "$this->tableAlias.$this->realField";
    $this->query->addWhere(0, $field, $this->value);
  }

}
