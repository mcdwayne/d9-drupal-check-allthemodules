<?php

namespace Drupal\available_updates_slack\Plugin\slack_notification\type;

use Drupal\Component\Plugin\PluginBase;
use Drupal\available_updates_slack\SlackNotificationTypeInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class TypeBase extends PluginBase implements SlackNotificationTypeInterface, ContainerFactoryPluginInterface {

    use StringTranslationTrait;

    /**
     * @var ConfigFactory
     */
    protected $config_factory;

    /**
     * TypeBase Constructor
     *
     * @param array $configuration
     * @param mixed $plugin_id
     * @param mixed $plugin_definition
     * @param TranslationInterface $string_translation
     * @param ConfigFactory $config_factory
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition,
        TranslationInterface $string_translation,
        ConfigFactory $config_factory){
            parent::__construct($configuration, $plugin_id, $plugin_definition);
            $this->stringTranslation = $string_translation;
            $this->config_factory = $config_factory;

    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
      return new static(
        $configuration,
        $plugin_id,
        $plugin_definition,
        $container->get('string_translation'),
        $container->get('config.factory')
      );
    }

  /**
     * Get the Notification Type Message
     *
     * @return string
     */
    abstract protected function getMessageHeading();

  /**
   * Get the notification color, it should be one of the following 'danger', 'notify'
   * @return string
   */
    abstract protected function definedColor();

    /**
     * Returns the site
     *
     * @return string site name
     */
    protected function getSiteName() {
        return \Drupal::config('system.site')->get('name');
    }

    /**
     * {@inheritdoc}
     */
    public function buildMessage(array $modules){
        return [
            'text' => "*{$this->getSiteName()}*: {$this->getMessageHeading()}",
            'attachments' => [[
                'text' => implode(PHP_EOL, $modules),
                'color' => $this->definedColor()
            ]],
            "mrkdwn" => true
        ];
    }
}
