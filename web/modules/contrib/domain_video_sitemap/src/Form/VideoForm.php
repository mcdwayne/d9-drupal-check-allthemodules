<?php

namespace Drupal\domain_video_sitemap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\domain_video_sitemap\DomainVideoList;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Video News sitemap settings for this site.
 */
class VideoForm extends ConfigFormBase {

  /**
   * The config object for the video sitemap settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Drupal\domain\DomainLoader definition.
   *
   * @var \Drupal\domain_video_sitemap\DomainVideoList
   */
  protected $domainVideoList;

  /**
   * Construct function.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\domain_video_sitemap\DomainVideoList $domainVideoList
   *   Video list object..
   */
  public function __construct(ConfigFactoryInterface $config_factory, DomainVideoList $domainVideoList) {
    $this->config = $config_factory;
    $this->domainVideoList = $domainVideoList;
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
        $container->get('config.factory'), $container->get('domain_video_sitemap.list')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'video_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['video_admin.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('video_admin.settings');
    $node_types = node_type_get_names();
    $list_nodes = $this->domainVideoList->domainVideoListNodes();
    $mine_types = $this->domainVideoList->domainVideoMimeTypes();
    $field_types = [
      'youtube' => 'Youtube Field',
      'files' => 'Files folder',
    ];
    if (isset($list_nodes['error'])) {
      $url = Url::fromRoute('domain.admin');
      $domain_link = $this->l($this->t('Domain records'), $url);
      $form['title']['#markup'] = $this->t('There is no Domain record yet.Please create a domain records.See link: @domain_list', ['@domain_list' => $domain_link]);
      return $form;
    }
    $form['help'] = [
      '#markup' => '<p>' . $this->t('Settings for controlling the <a href="@news-sitemap">Video sitemap file</a>.', ['@news-sitemap' => '/sitemap-video.xml']),
    ];
    $form['video_node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select the content types to include'),
      '#default_value' => $config->get('video_node_types') != '' ? $config->get('video_node_types') : array_keys($node_types),
      '#options' => $node_types,
    ];
    $form['video_cache_timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cache timeout (minutes)'),
      '#default_value' => $config->get('video_cache_timeout') != '' ? $config->get('video_cache_timeout') : '15',
      '#description' => $this->t('The number of minutes that the sitemap file will be cached for before it is regenerated.'),
    ];
    $form['video_sitemap_exclude_mime_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Exclude MIME types from the sitemap.'),
      '#default_value' => $config->get('video_sitemap_exclude_mime_types') != '' ? $config->get('video_sitemap_exclude_mime_types') : array_keys($mine_types),
      '#options' => $mine_types,
    ];
    $form['video_sitemap_field_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Source of video to include in sitemap.'),
      '#default_value' => $config->get('video_sitemap_field_types') != '' ? $config->get('video_sitemap_field_types') : array_keys($field_types),
      '#options' => $field_types,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $cache_value = $form_state->getValue('video_cache_timeout');
    if (!is_numeric($cache_value) || $cache_value <= 0) {
      $form_state->setErrorByName('video_cache_timeout', $this->t('Cache time should be number'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('video_admin.settings');
    $config->set('video_node_types', $form_state->getValue('video_node_types'));
    $config->set('video_sitemap_exclude_mime_types', $form_state->getValue('video_sitemap_exclude_mime_types'));
    $config->set('video_cache_timeout', $form_state->getValue('video_cache_timeout'));
    $config->set('video_sitemap_field_types', $form_state->getValue('video_sitemap_field_types'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
