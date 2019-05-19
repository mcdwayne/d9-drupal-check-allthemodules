<?php

namespace Drupal\simpleads\Form\Campaigns;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simpleads\Campaigns;

/**
 * Configure SimpleAdsCampaigns settings.
 */
class All extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simpleads_campaigns_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#attached']['library'][] = 'simpleads/admin.assets';

    $campaigns = new Campaigns();
    $form = $this->actionDropdown($form, $campaigns->getTypes());

    $form['campaigns'] = [
      '#type'        => 'table',
      '#tableselect' => FALSE,
      '#tabledrag'   => FALSE,
      '#empty'       => $this->t('There are no campaigns created.'),
      '#header'      => [
        $this->t('Name'),
        $this->t('Description'),
        $this->t('Type'),
        $this->t('Status'),
        '',
      ],
    ];

    foreach ($campaigns->loadAll() as $item) {
      $id = $item->getId();
      $type = $item->getType();
      $form['campaigns'][$id]['name'] = [
        '#markup' => $item->getCampaignName(),
      ];
      $form['campaigns'][$id]['description'] = [
        '#markup' => $item->getDescription(),
      ];
      $form['campaigns'][$id]['type'] = [
        '#markup' => $campaigns->getName($type),
      ];
      $form['campaigns'][$id]['status'] = [
        '#markup' => $item->getStatusName($item->getStatus()),
      ];
      $form['campaigns'][$id]['ops'] = [
        '#type' => 'operations',
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit'),
            'url'   => Url::fromRoute('simpleads.campaigns.edit', ['type' => $type, 'id' => $id]),
          ],
          'delete' => [
            'title' => $this->t('Delete'),
            'url'   => Url::fromRoute('simpleads.campaigns.delete', ['type' => $type, 'id' => $id]),
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
          'url'   => Url::fromRoute('simpleads.campaigns.new', ['type' => $id]),
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
          '#url'        => Url::fromRoute('simpleads.campaigns.new', ['type' => $id]),
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
