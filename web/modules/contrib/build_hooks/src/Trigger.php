<?php

namespace Drupal\build_hooks;

use Drupal\build_hooks\Entity\FrontendEnvironmentInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationManager;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Class Trigger.
 */
class Trigger implements TriggerInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  const DEPLOYMENT_STRATEGY_CRON = 'cron';
  const DEPLOYMENT_STRATEGY_ENTITYSAVE = 'entitysave';
  const BUILD_HOOKS_TOOLBAR_CACHE_TAG = 'build_hooks_toolbar';

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The http_client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The current_user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The string_translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $stringTranslation;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The logger.factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The deploy logger service.
   *
   * @var \Drupal\build_hooks\DeployLogger
   */
  protected $deployLogger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The cache tag invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagInvalidator;

  /**
   * Constructs a new Trigger object.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    ClientInterface $httpClient,
    AccountProxyInterface $currentUser,
    TranslationManager $stringTranslation,
    MessengerInterface $messenger,
    LoggerChannelFactoryInterface $logger,
    DeployLogger $deployLogger,
    EntityTypeManager $entityTypeManager,
    CacheTagsInvalidatorInterface $cacheTagInvalidator
  ) {
    $this->configFactory = $configFactory;
    $this->httpClient = $httpClient;
    $this->currentUser = $currentUser;
    $this->stringTranslation = $stringTranslation;
    $this->messenger = $messenger;
    $this->logger = $logger;
    $this->deployLogger = $deployLogger;
    $this->entityTypeManager = $entityTypeManager;
    $this->cacheTagInvalidator = $cacheTagInvalidator;
  }

  /**
   * Triggers all environments that are marked to fire on cron.
   */
  public function deployFrontendCronEnvironments() {
    $this->deployEnvironmentsByDeploymentStrategy(self::DEPLOYMENT_STRATEGY_CRON);
  }

  /**
   * Triggers all environments that are marked to fire on entity update.
   */
  public function deployFrontendEntityUpdateEnvironments() {
    $this->deployEnvironmentsByDeploymentStrategy(self::DEPLOYMENT_STRATEGY_ENTITYSAVE);
  }

  /**
   * Triggers all environments found by a specific deployment strategy.
   *
   * @param string $strategy
   *   The type of deployment strategy.
   */
  private function deployEnvironmentsByDeploymentStrategy(string $strategy) {
    try {
      /** @var \Drupal\build_hooks\Entity\FrontendEnvironmentInterface $environment */
      $environments = $this->entityTypeManager->getStorage('frontend_environment')
        ->loadByProperties(['deployment_strategy' => $strategy]);
      foreach ($environments as $environment) {
        $this->triggerBuildHookForEnvironment($environment);
      }
    }
    catch (\Exception $e) {
      $this->messenger()
        ->addWarning($this->t('Could not trigger deployments with strategy @strategy. Error message: @error', ['@strategy' => $strategy, '@error' => $e->getMessage()]));
    }
  }

  /**
   * Checks if we should show the environments in the upper menu.
   *
   * @return bool
   *   Boolean value.
   */
  public function showMenu() {
    if (!$this->isValidUser()) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Checks if the user has the permission to trigger deployments.
   *
   * @return bool
   *   Boolean value.
   */
  private function isValidUser() {
    return $this->currentUser->hasPermission('trigger deployments');
  }

  /**
   * Trigger a deployment for a frontend environment.
   *
   * @param \Drupal\build_hooks\Entity\FrontendEnvironmentInterface $frontendEnvironment
   *   The frontend environment entity to trigger the deployment for.
   */
  public function triggerBuildHookForEnvironment(FrontendEnvironmentInterface $frontendEnvironment) {
    /** @var \Drupal\build_hooks\Plugin\FrontendEnvironmentInterface $plugin */
    $plugin = $frontendEnvironment->getPlugin();
    $buildHookDetails = $plugin->getBuildHookDetails();
    try {
      $response_code = $this->triggerBuildHook($buildHookDetails);
      if ($response_code == 200) {
        // If the call was successful, set the latest deployment time
        // for this environment.
        $this->deployLogger->setLastDeployTimeForEnvironment($frontendEnvironment);
        $this->messenger()
          ->addMessage($this->t('Deployment triggered for environment @env .', ['@env' => $frontendEnvironment->label()]));
        $this->invalidateToolbarCacheTag();
      }
    }
    catch (GuzzleException $e) {
      $error = [
        'Failed to execute build hook for environment @env . Error message: <pre> @message </pre>',
        [
          '@message' => $e->getMessage(),
          '@env' => $frontendEnvironment->label(),
        ],
      ];
      $this->messenger()->addError($this->t('Failed to execute build hook for environment @env . Error message: <pre> @message </pre>', $error[1]));
      $this->logger->get('build_hooks')->error($error[0], $error[1]);
    }
  }

  /**
   * Triggers a build hook by the details.
   *
   * @param \Drupal\build_hooks\BuildHookDetails $buildHookDetails
   *   An object that holds the information about the call.
   *
   * @return int
   *   The status code of the operation.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function triggerBuildHook(BuildHookDetails $buildHookDetails) {
    $response = $this->httpClient->request(
      $buildHookDetails->getMethod(),
      $buildHookDetails->getUrl(),
      $buildHookDetails->getBody()
    );
    return $response->getStatusCode();
  }

  /**
   * Utility function to retrieve the cache tag to apply to the toolbar.
   *
   * @return string
   *   The toolbar cache tag.
   */
  public function getToolbarCacheTag() {
    return self::BUILD_HOOKS_TOOLBAR_CACHE_TAG;
  }

  /**
   * Invalidates the toolbar cache tag.
   */
  public function invalidateToolbarCacheTag() {
    $this->cacheTagInvalidator->invalidateTags([$this->getToolbarCacheTag()]);
  }

}
