<?php

/**
 * @file
 * Contains \Drupal\sharerich\Form\SharerichForm.
 */

namespace Drupal\sharerich\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Class SharerichForm.
 *
 * @package Drupal\sharerich\Form
 */
class SharerichForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $sharerich_set = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $sharerich_set->label(),
      '#description' => $this->t("Name for the Sharerich set."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $sharerich_set->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\sharerich\Entity\Sharerich::load',
      ),
      '#disabled' => !$sharerich_set->isNew(),
    );

    $form['services'] = $this->buildOverviewForm();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $services = array();
    $input = $form_state->getUserInput();
    foreach ($input['services'] as $name => $item) {
      if (isset($item['enabled']) && $item['enabled'] == TRUE) {
        $services[$name] = $item;
      }
    }
    uasort($services, array('Drupal\Component\Utility\SortArray', 'sortByWeightElement'));
    $sharerich_set = $this->entity;
    $sharerich_set->setServices($services);
    $status = $sharerich_set->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %id Sharerich.', [
          '%id' => $sharerich_set->id(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Updated the %id Sharerich.', [
          '%id' => $sharerich_set->id(),
        ]));
        // Clear block cache.
        Cache::invalidateTags(array('block_view'));

    }
    // Redirect.
    $form_state->setRedirectUrl($sharerich_set->urlInfo('collection'));
  }

  /**
   * Helper to build list of services.
   *
   * @return array form
   *   The form containing services.
   */
  protected function buildOverviewForm() {
    $form = array(
      '#type' => 'table',
      '#header' => array(
        $this->t('Service'),
        array(
          'data' => $this->t('Enabled'),
          'class' => array('checkbox'),
        ),
        $this->t('Weight'),
        array(
          'data' => $this->t('Markup'),
          'colspan' => 3,
        ),
      ),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'item-weight',
        ),
      ),
      '#attached' => array('library' => array('sharerich/sharerich')),
    );

    $form = array_merge($form, $this->buildOverviewFormRows());

    return $form;
  }

  /**
   * Helper to build rows of services.
   *
   * @return $form array.
   *   The form containing services.
   */
  protected function buildOverviewFormRows() {
    $storage = $this->entity->getServices();

    $weight = 0;
    foreach (sharerich_get_default_services() as $name) {

      $form[$name]['#attributes']['class'][] = 'draggable';

      $form[$name]['title'] = array(
        '#markup' => isset($storage[$name]['label']) ? $storage[$name]['label'] : ucfirst($name),
      );

      $form[$name]['enabled'] = array(
        '#type' => 'checkbox',
        '#title' => '',
        '#title_display' => 'invisible',
        '#default_value' => isset($storage[$name]['enabled']) ? $storage[$name]['enabled'] : FALSE,
      );

      $form[$name]['weight'] = array(
        '#type' => 'textfield',
        '#title' => '',
        '#title_display' => 'invisible',
        '#default_value' => isset($storage[$name]['weight']) ? $storage[$name]['weight'] : $weight++,
        '#attributes' => array('class' => array('item-weight')),
        '#size' => 3,
      );
      $form[$name]['#weight'] = $form[$name]['weight']['#default_value'];

      $form[$name]['markup'] = array(
        '#type' => 'textarea',
        '#default_value' => isset($storage[$name]['markup']) ? $storage[$name]['markup'] : sharerich_load_default_service($name),
        '#states' => array(
          'invisible' => array(
            ':input[name="services[' . $name . '][enabled]"]' => array('checked' => FALSE),
          ),
        ),
        '#attributes' => array('class' => array('markup')),
        '#suffix' => '<a class="button reset">' . $this->t('Reset') . '</a>',
      );

      $form[$name]['default_markup'] = array(
        '#type' => 'hidden',
        '#default_value' => sharerich_load_default_service($name),
        '#attributes' => array('class' => array('default-markup')),
      );

      $form[$name]['id'] = array(
        '#type' => 'hidden',
        '#value' => $name,
      );
    }
    uasort($form, array('Drupal\Component\Utility\SortArray', 'sortByWeightProperty'));

    return $form;
  }

}
