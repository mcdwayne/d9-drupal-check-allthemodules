<?php

namespace Drupal\coming_soon\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Provides a list controller for content_entity_example_contact entity.
 *
 * @ingroup content_entity_example
 */
class SubscriberListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {

    // Set the heading of the page.
    $heading = $this->t('Lis of the guests who subscribed to the notification system. You may donwload the list of the subscribers as a CSV file by clicking on the export button.');
    // Attach the heading to the page.
    $build['description'] = [
      '#markup' => $heading . ' &#8594; ',
    ];
    // Add a link to go to the batch page.
    $build['export_subscriber'] = [
      '#title' => $this->t('Export subscribers'),
      '#type' => 'link',
      '#url' => Url::fromRoute('coming_soon.export_subscribers_batch'),
      '#attributes' => [
        'class' => [
          'button',
          'button--primary',
          'button--small',
        ],
        'styles' => 'float: right;',
      ],
    ];
    // Add a line break.
    $build['line_break'] = [
      '#markup' => '<br><br>',
    ];
    // Render the data table.
    $build['table'] = parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the contact list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('Subscriber ID');
    $header['e-mail'] = $this->t('E-mail');
    $header['created'] = $this->t('Subscription date');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\coming_soon\Entity\Subscriber */
    $row['id'] = $entity->id();
    $row['name'] = $entity->email->value;
    $row['created'] = date('d-m-Y h:i:s', $entity->created->value);
    return $row + parent::buildRow($entity);
  }

}
