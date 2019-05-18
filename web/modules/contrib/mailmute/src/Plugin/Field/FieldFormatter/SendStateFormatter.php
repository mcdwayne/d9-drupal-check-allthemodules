<?php
/**
 * @file
 * Contains \Drupal\mailmute\Plugin\Field\FieldFormatter\SendStateFormatter.
 */

namespace Drupal\mailmute\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\mailmute\SendStateManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formatter for the 'sendstate' entity field.
 *
 * Rendering is delegated to
 * \Drupal\mailmute\Plugin\mailmute\SendState\SendStateInterface::display() on
 * the plugin referenced by the field value.
 *
 * @ingroup field
 *
 * @FieldFormatter(
 *   id = "sendstate",
 *   label = @Translation("Send state"),
 *   field_types = {
 *     "sendstate"
 *   }
 * )
 */
class SendStateFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The sendstate manager.
   *
   * @var \Drupal\mailmute\SendStateManagerInterface
   */
  protected $sendstateManager;

  /**
   * Constructs a new send state formatter.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, SendStateManagerInterface $sendstate_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->sendstateManager = $sendstate_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.sendstate')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Let the plugin take care of rendering.
    $sendstate = $this->sendstateManager->createInstance($items->plugin_id, (array) $items->configuration);
    $element = $sendstate->display();
    return array($element);
  }

}
