<?php

namespace Drupal\entity_counter\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_counter\Entity\EntityCounterInterface;
use Drupal\entity_counter\Plugin\EntityCounterSourceManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides an add form for entity counter source.
 */
class EntityCounterSourceAddForm extends EntityCounterSourceFormBase {

  /**
   * The entity counter source plugin manager.
   *
   * @var \Drupal\entity_counter\Plugin\EntityCounterSourceManager
   */
  protected $pluginManager;

  /**
   * Constructs an EntityCounterPluginSourceController object.
   *
   * @param \Drupal\entity_counter\Plugin\EntityCounterSourceManager $plugin_manager
   *   The entity counter source plugin manager.
   */
  public function __construct(EntityCounterSourceManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_counter.source')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityCounterInterface $entity_counter = NULL, $entity_counter_source = NULL) {
    $form = parent::buildForm($form, $form_state, $entity_counter, $entity_counter_source);

    // Throw access denied is source is excluded.
    if ($this->getEntityCounterSource()->isExcluded()) {
      throw new AccessDeniedHttpException();
    }

    $form['#title'] = $this->t('Add @label source', ['@label' => $this->getEntityCounterSource()->label()]);

    return $form;
  }

  /**
   * Creates a plugin instance and calculates its weight.
   *
   * @param string $entity_counter_source
   *   The entity counter source plugin id.
   *
   * @return \Drupal\entity_counter\Plugin\EntityCounterSourceInterface
   *   The created entity counter source instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function prepareEntityCounterSource(string $entity_counter_source) {
    /** @var \Drupal\entity_counter\Plugin\EntityCounterSourceInterface $entity_counter_source */
    $entity_counter_source = $this->pluginManager->createInstance($entity_counter_source);

    // Initialize the source an pass in the entity counter.
    $entity_counter_source->setEntityCounter($this->getEntityCounter());

    // Set the initial weight so this source comes last.
    $sources = $this->getEntityCounter()->getSources();
    $weight = 0;
    foreach ($sources as $source) {
      if ($weight < $source->getWeight()) {
        $weight = $source->getWeight() + 1;
      }
    }
    $entity_counter_source->setWeight($weight);

    return $entity_counter_source;
  }

}
