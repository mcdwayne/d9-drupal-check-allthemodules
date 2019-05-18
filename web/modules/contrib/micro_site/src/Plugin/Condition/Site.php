<?php

namespace Drupal\micro_site\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Site' condition.
 *
 * @Condition(
 *   id = "site",
 *   label = @Translation("Site"),
 *   context = {
 *     "site" = @ContextDefinition("entity:site", label = @Translation("Site"), required = FALSE)
 *   }
 * )
 */
class Site extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The domain negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructs a Site condition plugin.
   *
   * @param \Drupal\micro_site\SiteNegotiatorInterface $site_negotiator
   *   The site negotiator service.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(SiteNegotiatorInterface $site_negotiator, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->negotiator = $site_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $container->get('micro_site.negotiator'),
        $configuration,
        $plugin_id,
        $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['sites'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('When the following micro sites are active'),
      '#default_value' => $this->configuration['sites'],
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', $this->negotiator->loadOptionsList()),
      '#description' => $this->t('If you select no sites, the condition will evaluate to TRUE for all requests.'),
      '#attached' => array(
        'library' => array(
          'micro_site/drupal.micro_site',
        ),
      ),
    );
    $form['sites_front_page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Only the sites front page'),
      '#default_value' => $this->configuration['sites_front_page'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'sites' => array(),
      'sites_front_page' => FALSE,
    ) + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['sites'] = array_filter($form_state->getValue('sites'));
    $this->configuration['sites_front_page'] = $form_state->getValue('sites_front_page');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    // Use the site labels. They will be sanitized below.
    $sites = array_intersect_key($this->negotiator->loadOptionsList(), $this->configuration['sites']);
    if (count($sites) > 1) {
      $sites = implode(', ', $sites);
    }
    else {
      $sites = reset($sites);
    }

    if ($this->isNegated()) {
      return $this->configuration['sites_front_page'] ? $this->t('Active site is not @sites (only on the front page)', array('@sites' => $sites)) : $this->t('Active site is not @sites', array('@sites' => $sites));
    }
    else {
      return $this->configuration['sites_front_page'] ? $this->t('Active site is @sites (only on the front page)', array('@sites' => $sites)) : $this->t('Active site is @sites', array('@sites' => $sites));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $sites = $this->configuration['sites'];
    $only_front_page = $this->onlyFrontPage();
    $is_negated = $this->isNegated();

    if (!$this->onlyFrontPage()) {
      if (empty($sites) && !$this->isNegated()) {
        return TRUE;
      }

      // If the context did not load, derive from the negotiator active site.
      if (!$site = $this->getContextValue('site')) {
        $site = $this->negotiator->getActiveSite();
      }

      // No context found?
      if (empty($site)) {
        return FALSE;
      }

      if (empty($sites) && $site && $this->isNegated()) {
        return TRUE;
      }

      // NOTE: The context system handles negation for us.
      return (bool) in_array($site->id(), $sites);
    }
    // Condition only for the site front page.
    else {
      $site = $this->negotiator->loadFromRequest();
      if (empty($sites) && $site) {
        return TRUE;
      }

      // No context found?
      if (empty($site)) {
        return FALSE;
      }

      // NOTE: The context system handles negation for us.
      return (bool) in_array($site->id(), $sites);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'url.site';
    return $contexts;
  }


  public function onlyFrontPage() {
    return (bool) !empty($this->configuration['sites_front_page']);
  }

}
