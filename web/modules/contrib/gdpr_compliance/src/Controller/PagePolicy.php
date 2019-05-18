<?php

namespace Drupal\gdpr_compliance\Controller;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller routines for page routes.
 */
class PagePolicy extends ControllerBase {

  protected $moduleHandler;
  protected $requestStack;
  protected $configFactory;
  protected $dataFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('module_handler'),
      $container->get('request_stack'),
      $container->get('config.factory'),
      $container->get('date.formatter')
    );
  }

  /**
   * PagePolicy constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   Language manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request stack service.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Config factory service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   Date format service.
   */
  public function __construct(
    LanguageManagerInterface $languageManager,
    ModuleHandlerInterface $moduleHandler,
    RequestStack $requestStack,
    ConfigFactory $configFactory,
    DateFormatterInterface $dateFormatter
  ) {
    $this->moduleHandler = $moduleHandler;
    $this->requestStack = $requestStack;
    $this->configFactory = $configFactory;
    $this->dataFormatter = $dateFormatter;

    $lang = $languageManager->getCurrentLanguage()->getId();
    $this->lang = 'en';
    $langs = ['en', 'ru', 'de'];
    if (in_array($lang, $langs)) {
      $this->lang = $lang;
    }
  }

  /**
   * Page Title.
   */
  public function title() {
    $title = $this->t('Privacy and Cookie policy');
    $titles = [
      'en' => $title,
      'de' => $title,
      'ru' => $this->t('Agreement on the use of personal data'),
    ];
    $title = $titles[$this->lang];
    return $title;
  }

  /**
   * Constructs page from template.
   */
  public function page() {
    $lang = $this->lang;
    $path = $this->moduleHandler->getModule('gdpr_compliance')->getPath();
    $policy_path = DRUPAL_ROOT . "/$path/assets/policy/policy-{$lang}.html";
    $policy = file_get_contents($policy_path);
    $context = [
      'changed' => $this->dataFormatter->format(filemtime($policy_path), 'medium'),
      'url' => $this->requestStack->getCurrentRequest()->getHost(),
      'mail' => $this->configFactory->get('system.site')->get('mail'),
    ];
    $this->moduleHandler->alter('gdpr_compliance_policy', $policy, $context);
    return [
      'policy' => [
        '#type' => 'inline_template',
        '#template' => $policy,
        '#context' => $context,
      ],
    ];
  }

}
