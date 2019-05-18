<?php

namespace Drupal\httpbl\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\system\Form\PrepareModulesEntityUninstallForm;
use Drupal\httpbl\Entity\Host;
use Drupal\Component\Render\FormattableMarkup;


/**
 * Provides a form removing httpbl content entities data before uninstallation.
 *
 * Important!  This overrides the core method of removing module entities because
 * we also need to cleanup any records in the Ban module's table that were put 
 * there by Httpbl.
 */
class PrepareHttpblEntityUninstallForm extends PrepareModulesEntityUninstallForm {

  /**
   * The entity type ID of the entities to delete.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a PrepareModulesEntityUninstallForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type_manager);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'httpbl_prepare_modules_entity_uninstall';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);

    return $this->t('You are about to delete all @entity_type_plural + Ban_ip records banned by HttpBL.  Are you sure you want to do this?', ['@entity_type_plural' => $entity_type->getPluralLabel()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $check_link = Link::fromTextAndUrl(t('Please ensure all Http:BL Blocking is disabled'), Url::fromRoute('httpbl.admin_config'))->toString();
    $blacklist_report_link = Link::fromTextAndUrl(t('Http:BL blacklisted hosts'), Url::fromRoute('view.evaluated_hosts.page_banned'))->toString();
    // @see for the clue to this route.  http://drupal.stackexchange.com/questions/223405/how-to-get-route-name-of-a-view-page
    $message = $this->t('@check (otherwise new entities will be created during the process of deleting them).<br />', ['@check' => $check_link]);
    $message .= $this->t('This action affects two tables <strong>(httpbl_host and ban_ip)</strong> and cannot be undone.<br />');
    $message .= $this->t('Any blacklisted hosts in Http:BL will also be removed from Ban if found there.<br />');
    $message .= $this->t('You can preview all @blacklisted here.  These will be un-banned.<br />', ['@blacklisted' => $blacklist_report_link]);
    $message .= $this->t('Make a backup of your database if you want to be able to restore these items.');

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);

    return $this->t('Delete and Un-Ban all @entity_type_plural', ['@entity_type_plural' => $entity_type->getPluralLabel()]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type_id = $form_state->getValue('entity_type_id');

    $entity_type_plural = $this->entityTypeManager->getDefinition($entity_type_id)->getPluralLabel();
    $batch = [
      'title' => t('Deleting @entity_type_plural', [
        '@entity_type_plural' => $entity_type_plural,
      ]),
      'operations' => [
        [
          [__CLASS__, 'deleteContentEntities'], [$entity_type_id],
        ],
      ],
      'finished' => [__CLASS__, 'moduleBatchFinished'],
      'progress_message' => '',
    ];
    batch_set($batch);
  }

  /**
   * Deletes the content entities of the specified entity type.
   *
   * This function overrides Drupal core, in order to also manage non-entity
   * records in Ban module, created by this module.
   * @see comments below for details on what is being overridden.
   *
   * @param string $entity_type_id
   *   The entity type ID from which data will be deleted.
   * @param array|\ArrayAccess $context
   *   The batch context array, passed by reference.
   *
   * @internal
   *   This batch callback is only meant to be used by this form.
   */
  public static function deleteContentEntities($entity_type_id, &$context) {
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);

    // Set the entity type ID in the results array so we can access it in the
    // batch finished callback.
    $context['results']['entity_type_id'] = $entity_type_id;

    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = $storage->getQuery()->count()->execute();
    }

    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    $entity_ids = $storage->getQuery()
      ->sort($entity_type->getKey('id'), 'ASC')
      ->range(0, 10)
      ->execute();
    if ($entities = $storage->loadMultiple($entity_ids)) {
      //-----------------------------------------------------------------------
      // HERE'S THE OVERRIDE (everything in this function up to this point is core)!
      // Before deleting a batch of host entities, use them to find any matching
      // IPs in Ban module.
      //
      // Call BanIpManager service and check if this Host is also banned.
      $banManager = \Drupal::service('ban.ip_manager');

      foreach ($entities as $key => $host) {
        $host = Host::load($key);
        $host_ip = $host->getHostIp();
        // Find IPs that have also been banned by Httpbl.
        $banned = $banManager->isBanned($host_ip);

        // If banned (by Httpbl), un-ban them.
        if ($banned) {
          $banManager->unBanIp($host_ip);
          $message = new FormattableMarkup('Unbanned @ip banned by Httpbl while uninstalled.', ['@ip' => $host_ip]);
          \Drupal::logger('httpbl')->warning($message);
        }
      }
      // END OF OVERRIDE. Now remove the entities.
      // ----------------------------------------------------------------------
      $storage->delete($entities);
    }
    // Sometimes deletes cause secondary deletes. For example, deleting a
    // taxonomy term can cause it's children to be be deleted too.
    $context['sandbox']['progress'] = $context['sandbox']['max'] - $storage->getQuery()->count()->execute();

    // Inform the batch engine that we are not finished and provide an
    // estimation of the completion level we reached.
    if (count($entity_ids) > 0 && $context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
      $context['message'] = t('Deleting items... Completed @percentage% (@current of @total).', ['@percentage' => round(100 * $context['sandbox']['progress'] / $context['sandbox']['max']), '@current' => $context['sandbox']['progress'], '@total' => $context['sandbox']['max']]);

    }
    else {
      $context['finished'] = 1;
    }
  }

}