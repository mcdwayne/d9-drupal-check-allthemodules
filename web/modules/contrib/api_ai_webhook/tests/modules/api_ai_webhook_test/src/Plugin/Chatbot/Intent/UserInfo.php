<?php

namespace Drupal\api_ai_webhook_test\Plugin\Chatbot\Intent;

use Drupal\chatbot_api\Plugin\IntentPluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a user info intent.
 *
 * @Intent(
 *   id = "UserInfo",
 *   admin_label = @Translation("User Info Intent"),
 * )
 */
class UserInfo extends IntentPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ViewsIntent object.
   *
   * @param array $configuration
   *   Config.
   * @param string $plugin_id
   *   ID.
   * @param mixed $plugin_definition
   *   Definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process() {

    $param1 = $this->request->getIntentAttribute('generic-context-name.param1');
    $param2 = $this->request->getIntentAttribute('generic-context-name.param2');
    $this->response->addIntentAttribute('generic-context-name.param3', $param1 . $param2);

    $users = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $this->request->getIntentSlot('Staff')]);
    if ($users) {
      $user = reset($users);
      $this->response->setIntentResponse(strip_tags($user->field_user_info->processed));
      $this->response->setIntentDisplayCard($user->field_user_info->processed, 'User Info');
      return;
    }
    $this->response->setIntentResponse('There is no-one here by that name anymore.');
    $this->response->setIntentDisplayCard('There is no-one here by that name anymore', 'Whoops!');
  }

}
