<?php

namespace Drupal\ossfs\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to apply OSS image style to images.
 *
 * @Filter(
 *   id = "filter_oss_style",
 *   title = @Translation("Apply OSS style to images"),
 *   description = @Translation("Append a style to the image url query, must be under the <b>Track images uploaded via a Text Editor</b> filter."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *     "style" = "",
 *   },
 *   weight = 12
 * )
 */
class FilterOssStyle extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * An entity manager object.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a FilterOssStyle object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   An entity manager object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, ConfigFactoryInterface $config_factory) {
    $this->entityManager = $entity_manager;
    $this->configFactory = $config_factory;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $styles = $this->configFactory->get('ossfs.settings')->get('styles');
    $styles = array_unique(array_values($styles));
    $styles = array_combine($styles, $styles);
    $form['style'] = [
      '#type' => 'select',
      '#title' => $this->t('OSS image style'),
      '#options' => $styles,
      '#default_value' => $this->settings['style'] ?: NULL,
       '#description' => $this->t('This style will be appended to image url query: abc.jpg?x-oss-process=style/&lt;style&gt;. Configure the OSS styles at the <a href=":settings">OSS File System module settings page</a>.', [
         ':settings' => Url::fromRoute('ossfs.admin_settings')->toString(),
       ]),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (!empty($this->settings['style']) && stristr($text, 'data-entity-type="file"') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//img[@data-entity-type="file" and @data-entity-uuid]') as $node) {
        $uuid = $node->getAttribute('data-entity-uuid');

        // If there is a 'src' attribute, append the oss style to the url query.
        if ($src = $node->getAttribute('src')) {
          $file = $this->entityManager->loadEntityByUuid('file', $uuid);
          if ($file && strpos($file->getFileUri(), 'oss://') === 0) {
            $node->setAttribute('src', $src . '?x-oss-process=style/' . $this->settings['style']);
          }
        }
      }
      $result->setProcessedText(Html::serialize($dom));
    }

    return $result;
  }

}
