<?php

namespace Drupal\translation_extractor\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\translation_extractor\Service\TranslationExtractorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class StartScan.
 *
 * @package Drupal\translation_extractor\Form
 */
class StartScan extends FormBase {

  /**
   * The module settings object.
   *
   * @var ImmutableConfig
   */
  protected $settings;

  /**
   * Custom service to scan for translation strings.
   *
   * @var TranslationExtractorInterface
   */
  protected $translationExtractor;

  /**
   * Cache service provided by Drupal.
   *
   * @var CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * A list of available modules.
   *
   * @var \Drupal\Core\Extension\Extension[]
   */
  protected $moduleList;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('translation_extractor.settings'),
      $container->get('translation_extractor.scanner'),
      $container->get('cache.data')
    );
  }

  /**
   * StartScan constructor.
   *
   * @param ImmutableConfig $settings
   *   The module settings object.
   * @param TranslationExtractorInterface $translation_extractor
   *   Custom service to scan for translation strings.
   */
  public function __construct(
    ImmutableConfig $settings,
    TranslationExtractorInterface $translation_extractor,
    CacheBackendInterface $cache_backend
  ) {
    $this->settings = $settings;
    $this->translationExtractor = $translation_extractor;
    $this->cacheBackend = $cache_backend;

    if (($cache = $this->cacheBackend->get('translation_extractor.moduleList')) === FALSE) {
      $this->moduleList = array_filter(
        system_rebuild_module_data(),
        function (Extension $module) {
          return !preg_match('~^core~', $module->getPath());
        }
      );
      $this->cacheBackend->set('translation_extractor.moduleList', $this->moduleList, time() + 120);
    }
    else {
      $this->moduleList = $cache->data;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'translation_extractor.startForm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Prepare the select options.
    $options = array_map(function ($module) {
      return $module->info['name'];
    }, $this->moduleList);
    asort($options);

    return [
      'module' => [
        '#type' => 'select',
        '#title' => $this->t('Module to scan'),
        '#options' => $options,
        '#empty_option' => $this->t('Please choose'),
        '#required' => TRUE,
      ],
      'start' => [
        '#type' => 'submit',
        '#value' => $this->t('Start scan'),
      ],
      'settings' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        [
          '#type' => 'markup',
          '#markup' => $this->t(
            'Click @here to review/change the settings.',
            [
              '@here' => Link::fromTextAndUrl(
                $this->t('here'),
                Url::fromRoute('translation_extractor.settings'))->toString(),
            ]
          ),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->translationExtractor->scan(
      $this->settings,
      $form_state->getValue('module')
    );
    $form_state->setRedirect(
      'translation_extractor.scanResults',
      ['module' => $form_state->getValue('module')]
    );
  }

}
