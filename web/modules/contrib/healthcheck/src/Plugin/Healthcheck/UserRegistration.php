<?php


namespace Drupal\healthcheck\Plugin\healthcheck;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\healthcheck\Finding\Finding;
use Drupal\healthcheck\Finding\Report;
use Drupal\healthcheck\Plugin\HealthcheckPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Healthcheck(
 *  id = "user_registration",
 *  label = @Translation("User registration"),
 *  description = "Checks the user registration method.",
 *  tags = {
 *   "security",
 *  }
 * )
 */
class UserRegistration extends HealthcheckPluginBase  implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Pagecache constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $finding_service, $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $finding_service);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static (
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('healthcheck.finding'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFindings() {
    $config = $this->configFactory->get('user.settings');
    $findings = [];

    $register = $config->get('register');

    switch ($register) {
      case 'admin_only':
      case 'visitors_admin_approval':
        $findings[] = $this->noActionRequired($this->getPluginId());
        break;

      case 'visitors':
        $findings[] = $this->needsReview($this->getPluginId());
        break;

      default:
        $findings[] = $this->notPerformed($this->getPluginId());
        break;
    }

    return $findings;
  }

}
