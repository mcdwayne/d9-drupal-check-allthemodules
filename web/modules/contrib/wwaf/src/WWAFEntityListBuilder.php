<?php
namespace Drupal\wwaf;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

class WWAFEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['description'] = [
      '#prefix' => '<section style="margin: 20px 0">',
      '#markup' => $this->t('WWAF Entity implements a GPS Point on map.') .'<br>' .$this->t('These points are fieldable entities. You can manage the fields on the <a href="@adminlink">Structure admin page</a>.', array(
          '@adminlink' => \Drupal::urlGenerator()->generateFromRoute('wwaf.configuration.structure'),
        )),
      '#suffix' => '</section><br>',
    ];

    $build += parent::render();
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
    $header['id'] = $this->t('StoreID');
    $header['name'] = $this->t('Store Name');
    $header['gps'] = $this->t('Geolocation');
    return $header + parent::buildHeader();
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\wwaf\Entity\WWAFEntity */
    $row['id'] = $entity->id();
    $row['name'] = $entity->link();
    $row['gps'] = $entity->gps->value;
    return $row + parent::buildRow($entity);
  }
}