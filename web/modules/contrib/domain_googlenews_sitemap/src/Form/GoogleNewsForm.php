<?php

namespace Drupal\domain_googlenews\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\domain_googlenews\DomainGoogleNewsList;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Google News sitemap settings for this site.
 */
class GoogleNewsForm extends ConfigFormBase {

  /**
   * The config object for the googlenews sitemap settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Drupal\domain\DomainLoader definition.
   *
   * @var \Drupal\domain_googlenews\DomainGoogleNewsList
   */
  protected $domainGoogleNewsList;

  /**
   * Construct function.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\domain_googlenews\DomainGoogleNewsList $domainGoogleNewsList
   *   Googlenews list object..
   */
  public function __construct(ConfigFactoryInterface $config_factory, DomainGoogleNewsList $domainGoogleNewsList) {
    $this->config = $config_factory;
    $this->domainGoogleNewsList = $domainGoogleNewsList;
  }

  /**
   * Create function return static domain loader configuration.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Load the ContainerInterface.
   *
   * @return \static
   *   return domain loader configuration.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('domain_googlenews.list')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'googlenews_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['googlenews_admin.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('googlenews_admin.settings');
    $node_types = node_type_get_names();
    $list_nodes = $this->domainGoogleNewsList->domainGooglenewsListNodes();
    if (isset($list_nodes['error'])) {
      $domain_link = Link::createFromRoute($this->t('Domain records'), 'domain.admin')->toString();
      $form['title']['#markup'] = $this->t('There is no Domain record yet. Please create a domain records. See link: @domain_list', ['@domain_list' => $domain_link]);
      return $form;
    }
    $form['help'] = [
      '#markup' => '<p>' . $this->t('Settings for controlling the <a href="@news-sitemap">Google News sitemap file</a>.', ['@news-sitemap' => '/googlenews.xml']),
    ];
    $form['count'] = [
      '#markup' => '<p>' . $this->t('There are currently @count node(s) suitable for output.', ['@count' => count($list_nodes) != '' ? count($list_nodes) : 0]) . "</p>\n",
    ];
    $form['googlenews_publication_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publication name'),
      '#default_value' => $config->get('googlenews_publication_name'),
      '#description' => $this->t("Leave blank to use the site's name instead: :site_name", [':site_name' => $this->config('system.site')->get('name')]),
    ];
    $form['googlenews_node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select the content types to include'),
      '#default_value' => $config->get('googlenews_node_types') != '' ? $config->get('googlenews_node_types') : array_keys($node_types),
      '#options' => $node_types,
    ];
    $form['googlenews_cache_timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cache timeout (minutes)'),
      '#default_value' => $config->get('googlenews_cache_timeout') != '' ? $config->get('googlenews_cache_timeout') : '15',
      '#description' => $this->t('The number of minutes that the sitemap file will be cached for before it is regenerated.'),
    ];
    $form['googlenews_content_hours'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum content age (hours)'),
      '#default_value' => intval($config->get('googlenews_content_hours') != '' ? $config->get('googlenews_content_hours') : '48'),
      '#description' => $this->t('All content (nodes) created within this number of hours will be included in the sitemap file. It is recommended to leave this at the default of 48 hours.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $cache_value = $form_state->getValue('googlenews_cache_timeout');
    if (!is_numeric($cache_value) || $cache_value <= 0) {
      $form_state->setErrorByName('googlenews_cache_timeout', $this->t('Cache time should be number'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('googlenews_admin.settings');
    $config->set('googlenews_publication_name', $form_state->getValue('googlenews_publication_name'));
    $config->set('googlenews_node_types', $form_state->getValue('googlenews_node_types'));
    $config->set('googlenews_cache_timeout', $form_state->getValue('googlenews_cache_timeout'));
    $config->set('googlenews_content_hours', $form_state->getValue('googlenews_content_hours'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
