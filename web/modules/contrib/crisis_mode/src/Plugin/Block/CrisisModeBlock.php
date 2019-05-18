<?php

namespace Drupal\crisis_mode\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a 'Crisis' block.
 *
 * @Block(
 *   id = "crisis_mode_block",
 *   admin_label = @Translation("Crisis Mode Block"),
 * )
 */
class CrisisModeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Language\LanguageManagerInterface definition.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration,
  $plugin_id,
  $plugin_definition,
  ConfigFactoryInterface $config_factory,
  LanguageManagerInterface $language_manager,
  EntityTypeManagerInterface $entityTypeManager,
  AliasManagerInterface $alias_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entityTypeManager;
    $this->aliasManager = $alias_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
  array $configuration,
  $plugin_id,
  $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('path.alias_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $language_manager = $this->languageManager;
    $current_language = $language_manager->getCurrentLanguage()->getId();

    if ($config_data = $config = $this->configFactory->get('crisis_mode.settings')) {
      $config_data = $config_data->getRawData();

      $content = [];
      foreach ($config_data as $key => $config) {
        $content[$key] = $config;
      }

      // Show default texts if no translations exist for the current language.
      if ($config_data['langcode'] != $current_language) {
        $translated_config_data = $this->languageManager->getLanguageConfigOverride($current_language, 'crisis_mode.settings');
        if (!$translated_config_data->isNew()) {
          $content['crisis_mode_title'] = $translated_config_data->get('crisis_mode_title');
          $content['crisis_mode_text'] = $translated_config_data->get('crisis_mode_text');
          $content['crisis_mode_link_title'] = $translated_config_data->get('crisis_mode_link_title');
        }
      }

      // Preprocess block image.
      if (isset($content['crisis_mode_block_image'][0])) {
        $fid = $content['crisis_mode_block_image'][0];
        $file = $this->entityTypeManager->getStorage('file')->load($fid);
        $url = ImageStyle::load('medium')->buildUrl($file->getFileUri());
        $content['crisis_mode_block_image'] = $url;
      }

      // Preprocess background block image.
      if (isset($content['crisis_mode_background_image'][0])) {
        $fid = $content['crisis_mode_background_image'][0];
        $file = $this->entityTypeManager->getStorage('file')->load($fid);
        $path = $file->getFileUri();
        $url = file_create_url($path);
        $markup = 'background-image: url(' . $url . ');';
        $content['crisis_mode_background_image'] = $markup;
      }

      // Preprocess background block image.
      if (isset($content['crisis_mode_background_color'][0])) {
        $markup = 'background-color: ' . $content['crisis_mode_background_color'] . ';';
        $content['crisis_mode_background_color'] = $markup;
      }

      // Preprocess link if node is available.
      if ($content['crisis_mode_node']) {
        $node = $this->entityTypeManager->getStorage('node')->load($content['crisis_mode_node']);
        $content['crisis_mode_node'] = '';
        if ($node) {
          $content['crisis_mode_node'] = $this->aliasManager
            ->getAliasByPath('/node/' . $node->id());
          if (empty($content['crisis_mode_link_title'])) {
            $content['crisis_mode_link_title'] = $this->t('More Information');
          }
        }
      }
    }

    return [
      '#theme' => 'crisis_mode',
      '#content' => $content,
      '#attached' => [
        'library' => ['crisis_mode/crisis_mode'],
      ],
    ];
  }

}
