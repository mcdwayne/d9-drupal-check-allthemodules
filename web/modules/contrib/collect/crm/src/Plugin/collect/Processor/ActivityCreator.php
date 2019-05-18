<?php
/**
 * @file
 * Contains \Drupal\collect_crm\Plugin\collect\Processor\ActivityCreator.
 */

namespace Drupal\collect_crm\Plugin\collect\Processor;

use Drupal\collect\Processor\ProcessorBase;
use Drupal\collect\TypedData\CollectDataInterface;
use Drupal\collect\TypedData\TypedDataProvider;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Matches and/or creates a CRM Core Activity.
 *
 * @Processor(
 *   id = "activity_creator",
 *   label = @Translation("Activity creator"),
 *   description = @Translation("Creates a CRM Core Activity entity, including matched contacts.")
 * )
 */
class ActivityCreator extends ProcessorBase {

  /**
   * The injected entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new ActivityCreator processor instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, TypedDataProvider $typed_data_provider, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $typed_data_provider);
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('logger.factory')->get('collect'),
      $container->get('collect.typed_data_provider'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(CollectDataInterface $data, array &$context) {
    // Missing default bundle: log it and give up creating a new activity.
    if (!$this->entityManager->getStorage('crm_core_activity_type')->load('collect')) {
      $this->logger->warning('Could not create new activity: bundle @bundle missing', ['@bundle' => 'collect']);
      return;
    }

    // Missing container reference field: log it and save new activity
    // (container reference will be ignored).
    if (!$this->entityManager->getStorage('field_config')->load('activity_type.collect.collect_container')) {
      $this->logger->warning('Could not set the container reference on new activity: field @name missing on bundle @bundle', ['@name' => 'collect_container', '@bundle' => 'collect']);
    }

    $title = NULL;
    if ($title_property = $this->getConfigurationItem('title_property')) {
      try {
        $title = $data->get($title_property);
      }
      catch (\InvalidArgumentException $e) {
        $this->logger->warning('Activity title property @name not available', ['@name' => $title_property]);
      }
    }

    $date = DrupalDateTime::createFromTimestamp($data->getContainer()->getDate(), new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
    $activity_date = $date->format(DATETIME_DATETIME_STORAGE_FORMAT);

    // Create the activity.
    /** @var \Drupal\crm_core_activity\Entity\Activity $activity */
    $activity = $this->entityManager->getStorage('crm_core_activity')->create([
      'type' => 'collect',
      'title' => $title,
      'activity_date' => $activity_date,
      'collect_container' => $data->getContainer()->id(),
    ]);

    // Add participants to the activity.
    if (isset($context['contacts'])) {
      foreach ($context['contacts'] as $relation => $contacts) {
        foreach ($contacts as $contact) {
          // The participants field does not keep track of relation.
          // @todo make activity relationship aware.
          $activity->addParticipant($contact);
        }
      }
    }

    $activity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['activity_type_notice'] = [
      '#markup' => '<p>' . $this->t('Activities are created with type %type, and use the field %field to reference to the original container. The correct workflow is dependent on the existence of these.', ['%type' => 'collect', '%field' => 'collect_container']) . '</p>',
    ];
    $options = $this->getPropertyDefinitionOptions('string');
    $form['title_property'] = [
      '#type' => 'select',
      '#title' => $this->t('Title property'),
      '#description' => $this->t('The string property to use as activity title.'),
      '#options' => $options,
      '#default_value' => $this->getConfigurationItem('title_property'),
      '#required' => TRUE,
      '#disabled' => !$options,
    ];
    return $form;
  }

}
