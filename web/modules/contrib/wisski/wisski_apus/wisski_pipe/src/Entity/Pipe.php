<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\Entity\Profile.
 */

namespace Drupal\wisski_pipe\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\wisski_pipe\PipeInterface;
use Drupal\wisski_pipe\ProcessorCollection;
use Psr\Log\LoggerInterface;

 /** 
 * Defines the wisski_pipe pipe entity.
 *
 * @ConfigEntityType(
 *   id = "wisski_pipe",
 *   label = @Translation("WissKI Pipe"),
 *   handlers = {
 *     "list_builder" = "Drupal\wisski_pipe\PipeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\wisski_pipe\Form\Pipe\AddForm",
 *       "edit" = "Drupal\wisski_pipe\Form\Pipe\EditForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer wisski pipes",
 *   config_prefix = "wisski_pipe",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "collection" = "/admin/config/wisski_apus/pipe",
 *     "edit-form" = "/admin/config/wisski_apus/pipe/manage/{wisski_pipe}",
 *     "delete-form" = "/admin/config/wisski_apus/pipe/manage/{wisski_pipe}/delete"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "processors"
 *   }
 * )
 */
class Pipe extends ConfigEntityBase implements PipeInterface {
  

  /**
   * The ID of this pipe.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable label of this pipe.
   *
   * @var string
   */
  protected $label;

  
  /**
   * The description for the pipe
   *
   * @var string
   */
  protected $description;


  /**
   * The array with the pipe processors' confiuration
   *
   * @var array
   */
  protected $processors = [];
  

  /**
   * The collection of processors
   *
   * @var ProcessorCollection
   */
  protected $processorCollection;


  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return array(
      'processors' => $this->getProcessors(),
    );
  }


  /**
   * Returns the attribute manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   The attribute manager.
   */
  protected function getProcessorManager() {
    return \Drupal::service('plugin.manager.wisski_pipe.processor');
  }

  
  /**
   * {@inheritdoc}
   */
  public function getProcessor($processor_id) {
    return $this->getProcessors()->get($processor_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessors() {
    if (!$this->processorCollection) {
      $this->processorCollection = new ProcessorCollection($this->getProcessorManager(), $this->processors);
      $this->processorCollection->sort();
    }
    return $this->processorCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function addProcessor(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator()->generate();
    $this->getProcessors()->addInstanceId($configuration['uuid'], $configuration);
    return $configuration['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function removeProcessor($processor_id) {
    unset($this->processors[$processor_id]);
    $this->getProcessors()->removeInstanceId($processor_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessorConfig($processor_id, array $configuration) {
    $this->processors[$processor_id] = $configuration;
    $this->getProcessors()->setInstanceConfiguration($processor_id, $configuration);
    return $this;
  }


  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }


  /**
   * {@inheritdoc}
   */
  public function setDescription($d) {
    $this->description = trim($d);
  }
  
  
  /**
   * {@inheritdoc}
   */
  public function getTags() {
    
    $tags = [];
    $processors = $this->getProcessors();
    foreach ($processors as $p) {
      $tags = array_merge($tags, $p->getTags());
    }
    return $tags;

  }
  
  
  /**
   * {@inheritdoc}
   */
  public function run($data, $ticket = '', LoggerInterface $logger = NULL) {

    $processors = $this->getProcessors();
    foreach ($processors as $p) {
      $data = $p->run($data, $ticket, $logger);
    }
    return $data;

  }


}
 
