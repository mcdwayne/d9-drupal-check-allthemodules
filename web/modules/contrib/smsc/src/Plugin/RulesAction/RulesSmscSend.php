<?php

/**
 * @file
 */

namespace Drupal\smsc\Plugin\RulesAction;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\smsc\Smsc\DrupalSmscInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides a 'Send SMSC message' action.
 *
 * @RulesAction(
 *   id = "smsc_rules_send",
 *   label = @Translation("Send SMSC message"),
 *   category = @Translation("SMSC"),
 *   context = {
 *     "phones" = @ContextDefinition("string",
 *       label = @Translation("Phones"),
 *       description = @Translation("Phones list (coma separated) or single
 *       phone.")
 *     ),
 *     "message" = @ContextDefinition("string",
 *       label = @Translation("Message"),
 *       description = @Translation("Message text.")
 *     ),
 *     "translit" = @ContextDefinition("boolean",
 *       label = @Translation("Transliterate"),
 *       description = @Translation("Transliterate message."),
 *       required = false
 *     ),
 *     "sender" = @ContextDefinition("string",
 *       label = @Translation("Sender ID"),
 *       description = @Translation("Sender ID."),
 *       required = false
 *     )
 *   }
 * )
 */
class SmscRulesSend extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * DrupalSmsc definition.
   *
   * @var \Drupal\smsc\Smsc\DrupalSmscInterface
   */
  protected $drupalSmsc;

  /**
   * Constructs a UserBlock object.
   *
   * @param array                                 $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string                                $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed                                 $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\smsc\Smsc\DrupalSmscInterface $drupalSmsc
   *   The DrupalSmsc definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DrupalSmscInterface $drupalSmsc) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->drupalSmsc = $drupalSmsc;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('smsc')
    );
  }

  /**
   * Send Message.
   *
   * @param string $phones
   * @param string $message
   * @param bool   $translit
   * @param string $sender
   */
  protected function doExecute($phones, $message, $translit = NULL, $sender = NULL) {
    $options = [
      'translit' => $translit,
      'sender'   => $sender,
    ];

    $this->drupalSmsc::sendSms($phones, $message, $options);
  }
}