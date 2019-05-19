<?php

namespace Drupal\smallads\Plugin\DevelGenerate;

use Drupal\smallads\Entity\SmalladInterface;
use Drupal\smallads\Entity\Smallad;
use Drupal\smallads\Entity\SmalladType;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\comment\Entity\Comment;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Utility\Random;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a DevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "smallad",
 *   label = @Translation("smallads"),
 *   description = @Translation("Generate a given number of smallads.."),
 *   url = "smallad",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 100,
 *     "kill" = TRUE,
 *     "type" = "any",
 *     "since" =  0
 *   }
 * )
 */
class SmalladDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  const MAX = 100;

  /**
   * The smallad storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $smalladStorage;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The transaction storage.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   * Uids of all users
   *
   * @var array
   */
  protected $uids;

  /**
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   * @param EntityTypeManagerInterface $entity_type_manager
   * @param ModuleHandlerInterface $module_handler
   * @param QueryFactory $entity_query_factory
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, QueryFactory $entity_query_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->smalladStorage = $entity_type_manager->getStorage('smallad');
    $this->moduleHandler = $module_handler;
    $this->entityQueryFactory = $entity_query_factory;
    $this->uids = $this->entityQueryFactory->get('user')->condition('status', TRUE)->execute();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['kill'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Delete all smallads</strong> before generating new content.'),
      '#default_value' => $this->getSetting('kill'),
    );
    $form['num'] = array(
      '#type' => 'number',
      '#title' => $this->t('How many smallads would you like to generate?'),
      '#default_value' => $this->getSetting('num'),
      '#required' => TRUE,
      '#min' => 0,
    );
    $types = array_keys(SmalladType::loadMultiple());
    $form['type'] = array(
      '#title' => $this->t('Type'),
      '#type' => 'select',
      '#options' => ['any' => $this->t('- All -')] + array_combine($types, $types),
      '#default_value' => 'any',
      '#min' => 0,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values) {
    $this->settings = $values + $this->getDefaultSettings();
    if ($this->getSetting('num') < static::MAX) {
      $this->generateContent();
    }
    else {
      $this->generateBatchContent();
    }
  }

  /**
   * Method responsible for creating a small number of smallads.
   *
   * @param array $values
   *   Kill, num, since.
   *
   * @throws \Exception
   */
  private function generateContent() {
    if (!empty($this->getSetting('kill'))) {
      $this->contentKill();
    }
    $this->since = $this->getSetting('since') ?: strtotime('-1 year');
    for ($i = 0; $i < $this->getSetting('num'); $i++) {
      $this->develGenerateSmalladAdd();
    }
    if (function_exists('drush_log') && $i % drush_get_option('feedback', 1000) == 0) {
      drush_log(dt('Completed @feedback smallads ', ['@feedback' => drush_get_option('feedback', 1000)], 'ok'));
    }
    $type_name = $this->getSetting('type') == 'any' ? $this->t('Small ad') : SmalladType::load($this->getSetting('type'))->label();
    $this->setMessage(
      $this->t('Created @count @types', ['@count' => $this->getSetting('num'), '@type' => $type_name])
    );
  }

  /**
   * Method responsible for creating more than 50 items at a time.
   */
  private function generateBatchContent() {
    // setFile doesn't include the file yet and devel_generate_batch_finished must be callable
    module_load_include('batch.inc', 'devel_generate');
    $batch_builder = (new BatchBuilder())
     ->setTitle(t('Generating Smallads'))
     ->setFile(drupal_get_path('module', 'devel_generate') . '/devel_generate.batch.inc')
     ->setFinishCallback('devel_generate_batch_finished');

    // Add the kill operation.
    if ($this->getSetting('kill')) {
      $batch_builder->addOperation('devel_generate_operation', [$this, 'batchContentKill', []]);
    }
    // Add the operations to create the ads.
    $batches = ceil($this->getSetting('num')/static::MAX);
    for ($num = 0; $num < $batches; $num++) {
      $batch_builder->addOperation('devel_generate_operation', [$this, 'batchContentAddSmallads', []]);
    }
    batch_set($batch_builder->toArray());
  }

  /**
   * Batch callback.
   */
  public function batchContentAddSmallads($values, &$context) {
    $this->since = $this->getSetting('since');
    $i = 0;
    if (!isset($context['results']['num'])) {
      $context['results']['num'] = 0;
    }
    while ($context['results']['num'] < $this->getSetting('num') and $i < Self::MAX) {
      $this->develGenerateSmalladAdd();
      $context['results']['num']++;
      $i++;
    }
  }

  public function batchContentKill($values, &$context) {
    $this->contentKill();
  }

  /**
   * Deletes all smallads acccording to type
   */
  protected function contentKill() {
    $props = [];
    if ($type = $this->getSetting('type')) {
      $props['type'] = $type;
    }
    if ($smallads = $this->smalladStorage->loadByProperties($props)) {
      $num = count($smallads);
      $this->smalladStorage->delete($smallads);
      $this->setMessage(
        $this->t(
          'DevelGenerate deleted %count @types.',
          [
            '%count' => $num,
            '@type' => $type ? SmalladType::load($type)->label() : $this->t('Small ad')
          ]
        )
      );
    }
  }

  /**
   * Create one smallad. Used by both batch and non-batch code branches.
   */
  protected function develGenerateSmalladAdd() {
    $type = $this->getSetting('type');
    if ($type == 'any') {
      // Pick a random type
      $types = array_keys(SmalladType::loadMultiple());
      $type = $types[rand(0, count($types) - 1)];
    }
    $first = [
      'A very nice',
      'A nearly expired',
      'A French',
      'A traditional',
      'An organic',
      'Jewish',
      'Refurbished',
      'Beautiful',
      'Hand-crafted',
      'Antique',
      'High-powered',
      '', '',
    ];
    $second = [
      'porcelaine',
      'green',
      'unwanted',
      'licenced',
      'brown',
      'underwater',
      'fried',
      'stress-tested',
      'double-loaded',
      'ex-rental',
      'gelatinous',
    ];
    $third = ['dragon',
      'antique',
      'dolly',
      'buffet',
      'ballet lessons',
      'donkey',
      'ladder',
      'mp3 player',
      'widgets',
      'armistice',
      '', '',
    ];
    $fourth = [
      'from the orient.',
      'in need of repair.',
      'unwanted gift.',
      'in perfect condition.',
      'for hire.',
      'latest model!',
      '', '', '',
    ];

    $category_ids = $this->entityQueryFactory->get('taxonomy_term')
      ->condition('vid', 'categories')
      ->execute();
    shuffle($category_ids);
    $props = [
      'type' => $type,
      'title' => $first[array_rand($first)] . ' ' . $second[array_rand($second)] . ' ' . $third[array_rand($third)] . ' ' . $fourth[array_rand($fourth)],
      'body' => $this->getRandom()->paragraphs(2),
      'categories' => array_slice($category_ids, 0, rand(1,2)),
      'uid' => $this->randomUid(),
      'directexchange' => rand(0, 1),
      'indirectexchange' => rand(0, 1),
      'money' => rand(0, 1),
    ];
    $smallad = Smallad::create($props);
    $smallad->created->value = rand($this->since, REQUEST_TIME);
    // Populate all additional fields with sample values.
    $this->populateFields($smallad);
    $smallad->save();
    if ($smallad->getFieldDefinition('comments')) {
      $this->addComments($smallad);
    }
  }

  /**
   * Get a random user uid.
   */
  private function randomUid() {
    return $this->uids[array_rand($this->uids)];
  }

  /**
   * Create comments and add them to a smallad.
   *
   * @param SmalladInterface $smallad
   *   Smallad to add comments to.
   */
  public function addComments(SmalladInterface $smallad) {
    $parents = array();
    $num_comments = mt_rand(1, 3);
    for ($i = 1; $i <= $num_comments; $i++) {
      switch ($i % 3) {
        case 0:
          // No parent.
        case 1:
          // Top level parent.
          $parents = $this->entityQueryFactory->get('comment')
            ->condition('pid', 0)
            ->condition('entity_id', $smallad->id())
            ->condition('entity_type', 'smallad')
            ->condition('field_name', 'comments')
            ->range(0, 1)
            ->execute();
          break;

        case 2:
          // Non top level parent.
          $parents = $this->entityQueryFactory->get('comment')
            ->condition('pid', 0, '>')
            ->condition('entity_id', $smallad->id())
            ->condition('entity_type', 'smallad')
            ->condition('field_name', 'comments')
            ->range(0, 1)
            ->execute();
          break;
      }
      $random = new Random();
      $stub = array(
        'entity_type' => 'smallad',
        'entity_id' => $smallad->id(),
        'comment_type' => 'smallad',
        'field_name' => 'comments',
        'created' => mt_rand($smallad->get('created')->value, REQUEST_TIME),
        'subject' => substr($random->sentences(mt_rand(2, 6), TRUE), 0, 63),
        'langcode' => $smallad->language()->getId(),
      );
      if ($parents) {
        $stub['pid'] = current($parents);
      }
      $comment = Comment::create($stub);

      // Populate all core fields on behalf of field.module.
      DevelGenerateBase::populateFields($comment);
      $comment->uid = $this->randomUid();
      $comment->setFieldname('comments');
      $comment->entity_type->value = 'smallad';
      $comment->entity_id->value = $smallad->id();
      $comment->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams($args) {
    $values['kill'] = drush_get_option('kill');
    $values['type'] = drush_get_option('type');
    $values['num'] = array_shift($args);
    return $values;
  }


}
