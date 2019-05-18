<?php

namespace Drupal\mail_entity_queue\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete a mail queue.
 */
class MailEntityQueueDeleteForm extends EntityDeleteForm {

  /**
   * The entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $query;

  /**
   * Constructs a new MailEntityQueueDeleteForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->query = $entity_type_manager->getStorage('mail_entity_queue_item')->getQuery();
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $elements_count = $this->query
      ->condition('queue', $this->entity->id())
      ->count()
      ->execute();

    // Do not allow to remove this mail queue if there is any element in
    // the mail queue item list.
    if ($elements_count) {
      $caption = '<p>' .
        $this->formatPlural(
          $elements_count,
          '%type is used by 1 element on your mail queue. You can not remove this queue until you have removed all of the %type elements.',
          '%type is used by @count elements on your mail queue. You may not remove %type until you have removed all of the %type elements.',
          ['%type' => $this->entity->label()]
        ) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = ['#markup' => $caption];

      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
