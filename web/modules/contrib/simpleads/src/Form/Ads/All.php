<?php

namespace Drupal\simpleads\Form\Ads;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simpleads\Ads;

/**
 * Advertisement listing form.
 */
class All extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simpleads_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#attached']['library'][] = 'simpleads/admin.assets';

    $form['actions'] = [
      '#type'   => 'actions',
      '#weight' => -10,
    ];

    $ads = new Ads();
    $form = $this->actionDropdown($form, $ads->getTypes());

    $form['advertisements'] = [
      '#type'        => 'table',
      '#tableselect' => FALSE,
      '#tabledrag'   => FALSE,
      '#empty'       => $this->t('There are no advertisements created. Please create new Group first.'),
      '#header'      => [
        $this->t('Name'),
        $this->t('Type'),
        $this->t('Group'),
        $this->t('Campaign'),
        $this->t('Status'),
        '',
      ],
    ];

    foreach ($ads->loadAll() as $item) {
      $id = $item->getId();
      $type = $item->getType();
      $group = $item->getGroup()->getGroupName();
      $campaign = $item->getCampaign()->getCampaignName();
      $form['advertisements'][$id]['name'] = [
        '#markup' => $item->getAdName(),
      ];
      $form['advertisements'][$id]['type'] = [
        '#markup' => $item->getName($type),
      ];
      $form['advertisements'][$id]['group'] = [
        '#markup' => !empty($group) ? $group : '-',
      ];
      $form['advertisements'][$id]['campaign'] = [
        '#markup' => !empty($campaign) ? $campaign : '-',
      ];
      $form['advertisements'][$id]['status'] = [
        '#markup' => $item->getStatusName($item->getStatus()),
      ];
      $form['advertisements'][$id]['ops'] = [
        '#type' => 'operations',
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit'),
            'url'   => Url::fromRoute('simpleads.ads.edit', ['type' => $type, 'id' => $id]),
          ],
          'delete' => [
            'title' => $this->t('Delete'),
            'url'   => Url::fromRoute('simpleads.ads.delete', ['type' => $type, 'id' => $id]),
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
  }

  /**
   * {@inheritdoc}
   */
  protected function actionDropdown($form, $types) {
    $links = [];
    if (count($types) > 1) {
      foreach ($types as $id => $name) {
        $links[$id] = [
          'title' => $this->t('+ New @name', ['@name' => $name]),
          'url'   => Url::fromRoute('simpleads.ads.new', ['type' => $id]),
          'attributes' => [
            'class' => ['button', 'form-submit'],
          ],
        ];
      }
      $form['actions']['links'] = [
        '#type'   => 'dropbutton',
        '#links'  => $links,
        '#prefix' => '<div class="simpleads-actions-wrapper">',
        '#suffix' => '</div>',
      ];
    }
    else {
      foreach ($types as $id => $name) {
        $form['actions']['links'] = [
          '#type'       => 'link',
          '#title'      => $this->t('+ New @name', ['@name' => $name]),
          '#url'        => Url::fromRoute('simpleads.ads.new', ['type' => $id]),
          '#attributes' => [
            'class' => ['button', 'button--primary', 'form-submit'],
          ],
          '#prefix'     => '<div class="simpleads-actions-wrapper single">',
          '#suffix'     => '</div>',
        ];
      }
    }
    return $form;
  }

}
