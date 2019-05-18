<?php

namespace Drupal\googlemap_block\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Class GoogleMapList.
 *
 * @package Drupal\googlemap_block\Form
 */
class GoogleMapList extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'googlemap_block.GoogleMap',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_map_location_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Reading location from databse.
    $query = db_select('google_map_location_list', 'u');
    $query->fields('u', ['lid', 'location_name']);
    $results = $query->execute()->fetchAll();
    // Add location link.
    $link = Link::createFromRoute($this->t('Add Location'), 'googlemap_block.add_locaton')->toString();
    $form['googlemaplist'] = [
      '#type' => 'table',
      '#header' => [$this->t('Name'), $this->t('Edit'), $this->t('Delete')],
      '#empty' => $this->t('There are no items yet. Add an item.', []),
    ];
    $form['#prefix'] = $link;
    foreach ($results as $list) {
      $edit_link = Link::createFromRoute($this->t('Edit'), 'googlemap_block.edit_locaton', ['location_id' => $list->lid])->toString();
      $delete_link = Link::createFromRoute($this->t('Delete'), 'googlemap_block.delete_locaton', ['location_id' => $list->lid])->toString();
      $form['googlemaplist'][$list->lid]['location_name'] = [
        '#markup' => $list->location_name,
      ];
      $form['googlemaplist'][$list->lid]['edit'] = [
        '#markup' => $edit_link,
      ];
      $form['googlemaplist'][$list->lid]['delete'] = [
        '#markup' => $delete_link,
      ];
    }

    return $form;
  }

}
