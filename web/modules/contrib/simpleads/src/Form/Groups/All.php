<?php

namespace Drupal\simpleads\Form\Groups;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\simpleads\Groups;

/**
 * Configure SimpleAdsGroups settings.
 */
class All extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simpleads_groups_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $groups = new Groups();

    $form['#attached']['library'][] = 'simpleads/admin.assets';

    $form['actions'] = [
      '#type'   => 'actions',
      '#weight' => -10,
    ];

    $form['actions']['links'] = [
      '#type'       => 'link',
      '#title'      => $this->t('+ New Group'),
      '#url'        => Url::fromRoute('simpleads.groups.new'),
      '#attributes' => [
        'class' => ['button', 'button--primary', 'form-submit'],
      ],
      '#prefix'     => '<div class="simpleads-actions-wrapper single">',
      '#suffix'     => '</div>',
    ];

    $form['groups'] = [
      '#type'        => 'table',
      '#tableselect' => FALSE,
      '#tabledrag'   => FALSE,
      '#empty'       => $this->t('There are no groups created. Please consider keeping ads with the same width in the same group so it gets properly rendered in blocks.'),
      '#header'      => [
        $this->t('Name'),
        $this->t('Description'),
        '',
      ],
    ];

    foreach ($groups->loadAll() as $item) {
      $id = $item->getId();
      $form['groups'][$id]['name'] = [
        '#markup' => $item->getGroupName(),
      ];
      $form['groups'][$id]['description'] = [
        '#markup' => $item->getDescription(),
      ];
      $form['groups'][$id]['ops'] = [
        '#type' => 'operations',
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit'),
            'url'   => Url::fromRoute('simpleads.groups.edit', ['id' => $id]),
          ],
          'delete' => [
            'title' => $this->t('Delete'),
            'url'   => Url::fromRoute('simpleads.groups.delete', ['id' => $id]),
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

}
