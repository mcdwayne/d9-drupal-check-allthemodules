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
 * Class FacebookForm.
 */
class FacebookForm extends FormBase {

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
   * Constructs a new FacebookForm object.
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
      '#description' => $this->t('The human-readable name of this Facebook feed.'),
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
      $url = $migration->source['urls'];
      if (preg_match('/\/v[0-9\.]+\/([\w\d\.]+)\/.*/', $url, $matches) === 1) {
        $propertyName = $matches[1];
      }
      else {
        $propertyName = NULL;
      }
    }
    else {
      $propertyName = NULL;
    }
    $form['property_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Property Name'),
      '#description' => $this->t('The page name for the property to retrieve, eg. https://www.facebook.com/%property.', ['%property' => 'MyPageName']),
      '#required' => TRUE,
      '#default_value' => $propertyName,
    ];

    $form['property_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#description' => $this->t('The Client ID value from the Facebook app created for this page.'),
      '#required' => TRUE,
      '#default_value' => $migration ? $migration->source['authentication']['client_id'] : '',
      '#maxlength' => 256,
    ];

    $form['property_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#description' => $this->t('The Client Secret value from the Facebook app created for this page.'),
      '#required' => TRUE,
      '#default_value' => $migration ? $migration->source['authentication']['client_secret'] : '',
      '#maxlength' => 256,
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

    // Page names can't have spaces. That's as much as I know for now.
    if (strpos($form_state->getValue('property_name'), ' ') !== FALSE) {
      $form_state->setErrorByName('property_name', $this->t('Facebook Page names must not contain spaces.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getValue('feed_machine_name');
    $feed_name = $form_state->getValue('feed_name');
    $page = $form_state->getValue('property_name');
    $client_id = $form_state->getValue('property_client_id');
    $client_secret = $form_state->getValue('property_client_secret');
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
        'migration_group' => 'social_migration_facebook_feeds_group',
        'label' => $feed_name,
        'source' => [
          'urls' => "https://graph.facebook.com/v2.10/$page/posts?fields=caption,created_time,description,is_hidden,id,link,message,name,permalink_url,picture,full_picture,is_published,status_type,story,type,updated_time",
          'authentication' => [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
          ],
        ],
        'process' => [
          'field_social_migration_f_parent' => [
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

      $newMigration = entity_create('migration', $config);
      $newMigration->save();

      drupal_set_message($this->t('Successfully created a new Facebook feed with id %id for the page %page.', [
        '%id' => $id,
        '%page' => $page,
      ]));
    }
    else {
      // Update the entity config.
      $migration = $this->entityTypeManager->getStorage('migration')->load(array_pop($current));
      $migration->set('label', $feed_name);
      $migration->set('migration_tags', [
        'cron_enabled' => $cron_enabled,
      ]);
      $source = $migration->get('source');
      preg_match('/\/v[0-9\.]+\/(\w+)\/.*/', $source['urls'], $needle);
      $source['urls'] = str_replace($needle[1], $page, $source['urls']);
      $source['authentication'] = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
      ];
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

      drupal_set_message($this->t('Successfully edited the Facebook feed with id %id for the page %page.', [
        '%id' => $id,
        '%page' => $page,
      ]));
    }

    $form_state->setRedirectUrl(Url::fromRoute('social_migration.facebook.list'));

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
