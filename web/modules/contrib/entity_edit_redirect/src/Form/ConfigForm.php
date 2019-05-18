<?php

namespace Drupal\entity_edit_redirect\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure example settings for Entity edit redirect module.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MessengerInterface $messenger) {
    parent::__construct($config_factory);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_edit_redirect_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'entity_edit_redirect.settings',
    ];
  }

  /**
   * Converts entity edit path pattern settings to form value.
   *
   * @param array $settings
   *   Entity edit path patterns settings.
   *
   * @return string
   *   Form value for of entity edit path pattern settings.
   */
  protected function convertPathPatternsToFormValue(array $settings) {
    $value = '';
    foreach ($settings as $entity_type => $entity_type_settings) {
      // If it is an array it contains key:value pairs where key is the entity
      // bundle and value is path pattern.
      if (is_array($entity_type_settings)) {
        foreach ($entity_type_settings as $entity_bundle => $path_pattern) {
          $value .= "$entity_type.$entity_bundle:$path_pattern";
        }
      }
      // Otherwise it is path pattern directly.
      else {
        $value .= "$entity_type:$entity_type_settings";
      }
      $value .= "\n";
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('entity_edit_redirect.settings');

    $form['append_destination'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Append destination'),
      '#description' => $this->t('If current url should be append as destination.'),
      '#default_value' => $config->get('append_destination'),
    ];

    $form['destination_querystring'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Destination querystring'),
      '#description' => $this->t('Destination will be appended as a querystring specified here.'),
      '#default_value' => $config->get('destination_querystring'),
    ];

    $form['base_redirect_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base redirect url'),
      '#description' => $this->t('A base url to redirect to.'),
      '#default_value' => $config->get('base_redirect_url'),
    ];

    $path_patterns = $config->get('entity_edit_path_patterns');
    $form['entity_edit_path_patterns'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Entity edit path patterns'),
      '#description' => $this->t('Contains a list of entity edit path patterns for particular entity types and their bundles (optional).<br />Note that the redirect will be applied only to the entity types (and their bundles) listed here. Enter one record per line.<br />The format is <strong>{entity_type}.{entity_bundle}:{path_pattern}</strong>. For example <em>node.article:/content/{uuid}/edit</em> or <em>user:/users/{uuid}/edit</em>.<br />A string "<em>{uuid}</em>" in the path will be replaced by actual uuid of entity.'),
      '#default_value' => $this->convertPathPatternsToFormValue($path_patterns),
      '#rows' => 20,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Extract entity edit path patterns from provided textarea value.
   *
   * @param string $value
   *   Value as provided in form in textarea element.
   *
   * @return array
   *   List of extracted entity edit path patterns.
   */
  protected function extractEntityEditPathPatterns($value) {
    $list = [];
    foreach (preg_split("/\r\n|\n|\r/", $value) as $record) {
      if (!$record) {
        continue;
      }
      // Pattern per record is {entity_type}.{entity_bundle}:{path_pattern}
      // so split by ':' first to check if second part (the path) is presented.
      $parts = explode(':', $record, 2);
      $path = isset($parts[1]) ? trim($parts[1]) : NULL;
      // Skip record if no url was extracted.
      if (!$path) {
        $this->messenger->addWarning($this->t('A record "@record" was skipped due to missing path.', ['@record' => $record]));
        continue;
      }
      // Split the first part by '.' to get entity type and entity bundle.
      $parts = explode('.', $parts[0], 2);
      $entity_type = trim($parts[0]);
      // Skip record if no entity type was extracted.
      if (!$entity_type) {
        $this->messenger->addWarning($this->t('A record "@record" was skipped due to missing entity type.', ['@record' => $record]));
        continue;
      }
      $entity_bundle = isset($parts[1]) ? trim($parts[1]) : NULL;
      // If no bundle is presented assign path directly to entity type.
      if (!$entity_bundle) {
        $list[$entity_type] = $path;
      }
      else {
        $list[$entity_type][$entity_bundle] = $path;
      }
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $path_patterns_value = $form_state->getValue('entity_edit_path_patterns');
    $path_patterns = $this->extractEntityEditPathPatterns($path_patterns_value);
    // Save the configuration.
    $this->configFactory->getEditable('entity_edit_redirect.settings')
      ->set('append_destination', $form_state->getValue('append_destination'))
      ->set('destination_querystring', $form_state->getValue('destination_querystring'))
      ->set('base_redirect_url', $form_state->getValue('base_redirect_url'))
      ->set('entity_edit_path_patterns', $path_patterns)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
