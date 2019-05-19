<?php

namespace Drupal\vsauce_sticky_popup\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class VstickyPopupConfigEntityForm.
 *
 * @package Drupal\vsauce_sticky_popup\Form
 */
class VstickyPopupConfigEntityForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity = $this->entity;
    $form['#prefix'] = '<div id="vsp-entity-form">';
    $form['#suffix'] = '</div>';

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t("Label for the Vsauce config entity."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\vsauce_sticky_popup\Entity\VstickyPopupConfigEntity::load',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['path_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path Id'),
      '#default_value' => $entity->pathId(),
    ];

    $form['p_sticky'] = [
      '#type' => 'details',
      '#title' => $this->t('Positions'),
      '#open' => TRUE,
    ];

    $form['p_sticky_popup'] = [
      '#type' => 'details',
      '#title' => $this->t('Sticky Content'),
      '#open' => TRUE,
    ];

    $form['p_sticky']['position_sticky_popup'] = [
      '#type' => 'select',
      '#title' => $this->t('Collapsible content'),
      '#options' => [
        'default' => $this->t('Default'),
        'top' => $this->t('Top'),
        'right' => $this->t('Right'),
        'bottom' => $this->t('Bottom'),
        'left' => $this->t('Left'),
      ],
      '#default_value' => $entity->positionStickyPopup(),
      '#ajax' => [
        'callback' => [
          $this,
          'availableOptions',
        ],
      ],
    ];

    $defaultValue = $form_state->getValue('position_open_button') ? $form_state->getValue('position_open_button') : $entity->positionStickyPopup();
    $defaultPositionStickyPopup = $form_state->getValue('position_sticky_popup') ? $form_state->getValue('position_sticky_popup') : $entity->positionStickyPopup();

    $form['p_sticky']['position_open_button'] = [
      '#type' => 'select',
      '#title' => $this->t('Button open/close'),
      '#options' => $this->getOptionByPositionPopup($defaultPositionStickyPopup),
      '#default_value' => $defaultValue,
    ];

    $form['p_sticky']['position_arrow'] = [
      '#type' => 'select',
      '#title' => $this->t('Position Text around arrow'),
      '#options' => [
        'default' => $this->t('Default'),
        'prefix' => $this->t('Prefix'),
        'suffix' => $this->t('Suffix'),
      ],
      '#default_value' => $entity->positionArrow(),
    ];

    $form['p_sticky_popup']['collapsed'] = [
      '#type' => 'select',
      '#title' => $this->t('Collapse'),
      '#options' => [
        1 => $this->t('Open'),
        0 => $this->t('Close'),
      ],
      '#required' => TRUE,
      '#default_value' => $entity->collapsed(),
    ];

    $form['p_sticky_popup']['tab_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tab Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->tabLabel(),
    ];

    $form['p_sticky_popup']['content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Type'),
      '#options' => [
        'text' => $this->t('text'),
        'html' => $this->t('html'),
      ],
      '#required' => TRUE,
      '#default_value' => $entity->contentType(),
    ];

    $form['p_sticky_popup']['content'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Content'),
      '#default_value' => $entity->content(),
      '#required' => TRUE,

    ];
    return $form;
  }

  /**
   * Provide available option by position.
   *
   * @param string $value
   *   String with current position value.
   *
   * @return array
   *   Array with available condition.
   */
  private function getOptionByPositionPopup($value) {
    switch ($value) {
      case 'top':
        $options = [
          '' => $this->t('Center'),
          'left' => $this->t('Left'),
          'right' => $this->t('Right'),
        ];
        break;

      case 'right':
        $options = [
          '' => $this->t('Center'),
          'top' => $this->t('Top'),
          'bottom' => $this->t('Bottom'),
        ];
        break;

      case 'bottom':
        $options = [
          '' => $this->t('Center'),
          'left' => $this->t('Left'),
          'right' => $this->t('Right'),
        ];
        break;

      case 'left':
        $options = [
          '' => $this->t('Center'),
          'left top' => $this->t('Top'),
          'left bottom' => $this->t('Bottom'),
        ];
        break;

      default:
        $options = [];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function availableOptions(array &$form, FormStateInterface $form_state) {

    $response = new AjaxResponse();
    $options = $this->getOptionByPositionPopup($form_state->getValue('position_sticky_popup'));

    $form['p_sticky']['position_open_button']['#options'] = $options;
    $response->addCommand(new ReplaceCommand('#vsp-entity-form', $form));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $vsauce_config_entity = $this->entity;
    $status = $vsauce_config_entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Vsauce Sticky Popup config entity.', [
          '%label' => $vsauce_config_entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Vsauce Sticky Popup config entity.', [
          '%label' => $vsauce_config_entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($vsauce_config_entity->toUrl('collection'));
  }

}
