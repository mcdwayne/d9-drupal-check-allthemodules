<?php

namespace Drupal\rocket_chat_api\RocketChat {

  /*
   * Created by 040lab b.v. using PhpStorm from Jetbrains.
   * User: Lawri van Buël
   * Date: 20/06/17
   * Time: 16:38
   */

  use Drupal\Core\Config\ConfigFactoryInterface;
  use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
  use Drupal\Core\Extension\ModuleHandlerInterface;
  use Drupal\Core\State\StateInterface;
  use Symfony\Component\DependencyInjection\ContainerInterface;

  /**
   * Class Drupal8Config connects the API with the drupal system.
   *
   * @package RocketChat
   */
  class Drupal8Config implements RocketChatConfigInterface, ContainerInjectionInterface {

    /**
     * The config factory.
     *
     * Subclasses should use the self::config() method, which may be overridden
     * to address specific needs when loading config, rather than this property
     * directly.
     * See \Drupal\Core\Form\ConfigFormBase::config() for an example of this.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected $config;

    protected $moduleHandler;

    protected $state;

    /**
     * Constructs a \Drupal\system\ConfigFormBase object.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   The factory for configuration objects.
     * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
     *   The ModuleHandler to interact with loaded modules.
     * @param \Drupal\Core\State\StateInterface $state
     *   The state interface to manipulate the States.
     */
    public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $moduleHandler, StateInterface $state) {
      $this->config = $config_factory->get('rocket_chat.settings');
      $this->moduleHandler = $moduleHandler;
      $this->state = $state;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
      return new static(
        $container->get('config.factory'),
        $container->get('module_handler'),
        $container->get('state')
      );
    }

    /**
     * {@inheritdoc}
     */
    public function getElement($elementName, $default = NULL) {
      switch ($elementName) {
        case 'rocket_chat_url':
          // Fallthrough and modify.
          $elementName = "server";
        default:
          $value = $this->config->get($elementName);
          if (empty($value)) {
            $value = $default;
          }
          return $value;

        case 'rocket_chat_uid':
          // Fallthrough.
        case 'rocket_chat_uit':
          // Fallthrough.
          return $this->state->get($elementName, $default);

      }
    }

    /**
     * {@inheritdoc}
     */
    public function setElement($elementName, $newValue) {
      $config = $this->config;
      switch ($elementName) {
        case 'rocket_chat_url':
          // Fallthrough and modify.
          $elementName = "url";
        default:
          $config->clear($elementName)->set($elementName, $newValue)->save();
          break;

        case 'rocket_chat_uid':
          // Fallthrough.
        case 'rocket_chat_uit':
          // Fallthrough.
          $this->state->delete($elementName);
          if (!empty($newValue)) {
            $this->state->set($elementName, $newValue);
          }
          break;

      }
    }

    /**
     * {@inheritdoc}
     */
    public function isDebug() {
      return $this->moduleHandler->moduleExists('devel');
    }

    /**
     * {@inheritdoc}
     */
    public function getJsonDecoder() {
      return '\Drupal\Component\Serialization\Json::decode';
    }

    /**
     * {@inheritdoc}
     */
    public function notify($message, $type) {
      return drupal_set_message($message, $type);
    }

  }

}
