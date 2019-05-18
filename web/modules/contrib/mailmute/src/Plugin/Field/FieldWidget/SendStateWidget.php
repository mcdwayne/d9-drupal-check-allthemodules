<?php
/**
 * @file
 * Contains \Drupal\mailmute\Plugin\Field\FieldWidget\SendStateWidget.
 */

namespace Drupal\mailmute\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\mailmute\SendStateManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Select widget for the 'sendstate' entity field.
 *
 * @ingroup field
 *
 * @FieldWidget(
 *   id = "sendstate",
 *   label = @Translation("Send state"),
 *   field_types = {
 *     "sendstate"
 *   },
 *   multiple_values = TRUE
 * )
 */
class SendStateWidget extends OptionsWidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Injected Send state plugin manager.
   *
   * @var \Drupal\mailmute\SendStateManagerInterface
   */
  protected $sendstateManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_defintion, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, SendStateManagerInterface $sendstate_manager) {
    parent::__construct($plugin_id, $plugin_defintion, $field_definition, $settings, $third_party_settings);
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
      $configuration['third_party_settings'],
      $container->get('plugin.manager.sendstate')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\mailmute\Plugin\mailmute\SendState\SendStateInterface $sendstate */
    $sendstate = $this->sendstateManager->createInstance($items->plugin_id, (array) $items->configuration);

    $element['#type'] = 'details';
    $element['plugin_id'] = array(
      '#type' => 'select',
      '#title' => $this->t('State'),
      '#description' => $this->t('The <dfn>send state</dfn> determines whether email should be stopped form being sent from the website to the associated address.'),
      '#options' => $this->getOptions($items->getEntity()),
      '#default_value' => $sendstate->getPluginId(),
    );
    $element['configuration'] = $sendstate->form();

    // Hide if user doesn't have admin privilege.
    $account = \Drupal::currentUser();
    $element['#access'] = $account->hasPermission('administer mailmute')
      || $account->id() == $items->getEntity()->id() && $account->hasPermission('change own send state');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    $options = parent::getOptions($entity);
    // Store modified items in a new variable to redefine the order.
    $new_options = array();
    foreach ($this->sendstateManager->getPluginHierarchyLevels() as $id => $level) {
      if (isset($options[$id])) {
        $new_options[$id] = str_repeat('- ', $level) . $options[$id];
      }
    }
    return $new_options;
  }

  /**
   * {@inheritdoc}
   */
  protected function sanitizeLabel(&$label) {
    // Select form inputs allow unencoded HTML entities, but no HTML tags.
    $label = Html::decodeEntities(strip_tags($label));
  }

}
