<?php

namespace Drupal\block_country\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'Country' condition.
 *
 * @Condition(
 *   id = "country_checker",
 *   label = @Translation("Country"),
 * )
 */
class BlockCountry extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The country manager.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Creates a new BlockCountry instance.
   *
   * @param \Drupal\Core\Locale\CountryManagerInterface $country_manager
   *   The country manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(CountryManagerInterface $country_manager, RequestStack $request_stack, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->countryManager = $country_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('country_manager'),
      $container->get('request_stack'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['country_list'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Country'),
      '#options' => $this->countryManager->getList(),
      '#default_value' => !empty($this->configuration['country_list']) && isset($this->configuration['country_list']) ? $this->configuration['country_list'] : '',
      '#multiple' => True,
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $selected_countries = $form_state->getValues();
    foreach ($selected_countries as $key => $value) {
      if ($key != 'negate') {
        $this->configuration[$key] = $value;
      }
    }
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['country_list']) && $this->configuration['negate'] == False) {
      return True;
    } else {
      $ip = $this->requestStack->getCurrentRequest()->getClientIp();
      $ret = true;
      if ($country = ip2country_get_country($ip)) {
        if (!in_array($country, $this->configuration['country_list']))  {
          $ret = false;
        }
      } else {
        $ret = false;
      }
      return $ret;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('shows the blocks if country condition satisfy.');
  }


  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
   return Cache::mergeContexts(parent::getCacheContexts(), array('ip:country'));
  }

}
