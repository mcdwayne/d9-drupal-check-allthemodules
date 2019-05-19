<?php

namespace Drupal\social_migration\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Config\ConfigManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\migrate_plus\Entity\Migration;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class InstagramForm.
 */
class InstagramForm extends FormBase {

  /**
   * Drupal\Core\Config\ConfigManager definition.
   *
   * @var \Drupal\Core\Config\ConfigManager
   */
  protected $configManager;

  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new InstagramForm object.
   */
  public function __construct(
    ConfigManager $config_manager,
    QueryFactory $query_factory,
    EntityTypeManager $entity_type_manager
  ) {
    $this->configManager = $config_manager;
    $this->entityQueryFactory = $query_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.manager'),
      $container->get('entity.query'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'soc_mig_admin_f_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Migration $migration = NULL) {
    $form['feed_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Feed Name'),
      '#description' => $this->t('The human-readable name of this Instagram feed.'),
      '#required' => TRUE,
      '#default_value' => $migration ? $migration->label() : '',
    ];
    $form['feed_machine_name'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine Name'),
      '#default_value' => $migration ? $migration->id() : '',
      '#machine_name' => [
        'source' => ['feed_name'],
        'exists' => [$this, 'migrationExists'],
      ],
      '#required' => TRUE,
      '#disabled' => $migration ? TRUE : FALSE,
    ];

    if ($migration) {
      $tags = $migration->migration_tags;
      $account_name = isset($tags['account']) && !empty($tags['account']) ? $tags['account'] : NULL;
    }
    else {
      $account_name = NULL;
    }
    $form['property_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Property Name'),
      '#description' => $this->t('The account name to retrieve, eg. https://www.instagram.com/%account. This field does not have any functional effect on the migration and is informational only.', ['%account' => 'MyAccountName']),
      '#default_value' => $account_name,
    ];

    if ($migration) {
      $url = $migration->source['urls'];
      if (preg_match('/\?access_token=(.*)$/', $url, $matches) === 1) {
        $token = $matches[1];
      }
      else {
        $token = '';
      }
    }
    else {
      $token = '';
    }
    $form['property_access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#description' => $this->t('The Access Token value from the Instagram app created for this page.'),
      '#required' => TRUE,
      '#default_value' => $token,
    ];

    $publishOnImport = 1;
    if ($migration) {
      $process = $migration->process;
      if (isset($process['status'])) {
        $publishOnImport = $process['status']['default_value'];
      }
    }
    $form['publish_on_import'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically publish on import'),
      '#description' => $this->t('Check this box to mark all imported content as "published" immediately on import.'),
      '#default_value' => $publishOnImport,
    ];

    if ($migration) {
      $tags = $migration->migration_tags;
      $cronEnabled = isset($tags['cron_enabled']) ? $tags['cron_enabled'] : TRUE;
    }
    else {
      $cronEnabled = TRUE;
    }
    $form['feed_cron_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Automatic Feed Import'),
      '#description' => $this->t('Check this box to enable automatic import of this feed. If unchecked, the feed can still be manually imported but will not automatically import.'),
      '#default_value' => $cronEnabled,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Account names can't have spaces. That's as much as I know for now.
    if (strpos($form_state->getValue('property_name'), ' ') !== FALSE) {
      $form_state->setErrorByName('property_name', $this->t('Instagram account names must not contain spaces.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getValue('feed_machine_name');
    $feed_name = $form_state->getValue('feed_name');
    $account_name = $form_state->getValue('property_name');
    $access_token = $form_state->getValue('property_access_token');
    $publish_on_import = $form_state->getValue('publish_on_import');
    $cron_enabled = $form_state->getValue('feed_cron_enabled');

    // Check if we're editing or adding.
    $current = $this->entityQueryFactory->get('migration')
      ->condition('id', $id)
      ->execute();
    $isNew = empty($current);

    if ($isNew) {
      // Create the entity config.
      $config = [
        'langcode' => 'en',
        'status' => 'true',
        'dependencies' => [],
        'id' => $id,
        'class' => NULL,
        'field_plugin_method' => NULL,
        'cck_plugin_method' => NULL,
        'migration_tags' => [
          'cron_enabled' => $cron_enabled,
        ],
        'migration_group' => 'social_migration_instagram_feeds_group',
        'label' => $feed_name,
        'source' => [
          'urls' => "https://api.instagram.com/v1/users/self/media/recent?access_token=$access_token",
        ],
        'process' => [
          'field_social_migration_i_parent' => [
            'default_value' => $id,
          ],
        ],
        'destination' => [
          'plugin' => 'entity:node',
        ],
        'migration_dependencies' => NULL,
      ];

      if ($publish_on_import == 0) {
        $config['process']['status'] = [
          'plugin' => 'default_value',
          'default_value' => $publish_on_import,
        ];
      }

      if (!empty($account_name)) {
        $config['migration_tags']['account'] = $account_name;
      }

      $newMigration = entity_create('migration', $config);
      $newMigration->save();

      drupal_set_message($this->t('Successfully created a new Instagram feed with id %id for the account %account.', [
        '%id' => $id,
        '%account' => $account_name,
      ]));
    }
    else {
      // Update the entity config.
      $migration = $this->entityTypeManager->getStorage('migration')->load(array_pop($current));
      $migration->set('label', $feed_name);
      if (!empty($account_name)) {
        $migration->set('migration_tags', [
          'account' => $account_name,
          'cron_enabled' => $cron_enabled,
        ]);
      }
      else {
        $migration->set('migration_tags', NULL);
      }
      $source = $migration->get('source');
      preg_match('/\?access_token=(.*)$/', $source['urls'], $needle);
      $source['urls'] = str_replace($needle[1], $access_token, $source['urls']);
      $migration->set('source', $source);

      $process = $migration->get('process');
      if ($publish_on_import == 0) {
        $process['status'] = [
          'plugin' => 'default_value',
          'default_value' => $publish_on_import,
        ];
      }
      else {
        unset($process['status']);
      }
      $migration->set('process', $process);

      $migration->save();

      drupal_set_message($this->t('Successfully edited the Instagram feed with id %id for the account %account.', [
        '%id' => $id,
        '%account' => $account_name,
      ]));
    }

    $form_state->setRedirectUrl(Url::fromRoute('social_migration.instagram.list'));

  }

  /**
   * Checks for an existing migration.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if this migration already exists, FALSE otherwise.
   */
  public function migrationExists($entity_id, array $element, FormStateInterface $form_state) {
    $query = $this->entityQueryFactory->get('migration');
    $result = $query->condition('id', $element['#field_prefix'] . $entity_id)
      ->execute();

    return (bool) $result;
  }

}
