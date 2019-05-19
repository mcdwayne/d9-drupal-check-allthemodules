<?php
/**
 * @file
 * Contains Drupal\social_counters\Form\SocialCountersConfigForm.
 */

namespace Drupal\social_counters\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the social counters entity edit forms.
 */
class SocialCountersConfigForm extends EntityForm {
  /**
   * Json serealizer.
   */
  protected $json_serializer;

  /**
   * Social Counters manager.
   */
  protected $social_counters_manager;

  /**
   * Constructs a ContentEntityForm object.
   */
  public function __construct(SerializationInterface $json_serializer, PluginManagerInterface $social_counters_manager, QueryFactory $query_factory) {
    $this->json_serializer = $json_serializer;
    $this->social_counters_manager = $social_counters_manager;
    $this->entityQueryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('serialization.json'),
      $container->get('plugin.manager.social_counters'),
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;
    $config = (object)$this->json_serializer->decode($entity->config);

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      // @todo Tt should work as label.
      '#default_value' => $entity->name,
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('Identifier'),
      '#default_value' => $entity->id(),
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => 'The identifier must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".',
      ),
      '#disabled' => !$entity->isNew(),
    );

    $plugin_definitions = $this->social_counters_manager->getDefinitions();
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      $plugin = $this->social_counters_manager->createInstance($plugin_id);
      $options[$plugin_id] = $plugin->label();
    }

    // Order options by alphabet.
    asort($options);

    // Get default plugin to add correct config fields.
    $first_plugin = array_keys($options);
    $default_plugin = $entity->plugin_id;
    $default_plugin = !empty($default_plugin) ? $default_plugin : $form_state->getValue('plugin_id');
    $default_plugin = !empty($default_plugin) ? $default_plugin : reset($first_plugin);
    $plugin = $this->social_counters_manager->createInstance($default_plugin);

    // We can change set plugin_id only when entity is created.
    $form['plugin_id'] = array(
      '#title' => $this->t('Plugin'),
      '#type' => 'select',
      '#required' => TRUE,
      '#disabled' => !($entity->isNew()),
      '#options' => $options,
      '#ajax' => array(
        'callback' => array($this, 'changeConfigFieldsCallback'),
        'wrapper' => 'config-wrapper',
        'event' => 'change',
      ),
      '#default_value' => $default_plugin,
    );

    $form['config'] = array(
      '#title' => $this->t('Configuration'),
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#prefix' => '<div id="config-wrapper">',
      '#suffix' => '</div>',
    );

    $plugin->entityForm($form['config'], $form_state, $config);

    return $form;
  }

  /**
   * Checks for an existing Social Counter.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   * @param FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if this format already exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element, FormStateInterface $form_state) {
    $query = $this->entityQueryFactory->get('social_counters_config');
    $result = $query->condition('id', $entity_id)
      ->execute();

    return (bool) $result;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $config = $form_state->getValue('config');
    $entity = $this->getEntity();
    $entity->config = $this->json_serializer->encode($config);
    $entity->save();
    $form_state->setRedirect('entity.social_counters_config.collection');
  }

  /**
   * Add necessary fields when plugin_id is changed.
   */
  public function changeConfigFieldsCallback(array &$form, FormStateInterface $form_state) {
    return $form['config'];
  }
}
