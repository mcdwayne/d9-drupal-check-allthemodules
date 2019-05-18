<?php

namespace Drupal\menu_link\Plugin\Menu\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\Form\MenuLinkDefaultForm;

/**
 * Provides the menu link edit form for the field-based menu link.
 */
class MenuLinkFieldForm extends MenuLinkDefaultForm {

  /**
   * The Menu link.
   *
   * @var \Drupal\menu_link\Plugin\Menu\MenuLinkField
   */
  protected $menuLink;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $entity = $this->menuLink->getEntity();

    $form['info'] = [
      '#type' => 'item',
      '#title' => $this->t('This link is provided by the %type: <a href="@url">@label</a>. The path cannot be edited.', [
        '%type' => $entity->getEntityType()->getLabel(),
        '@url' => $entity->url(),
        '@label' => $entity->label(),
      ]),
    ];

    return $form;
  }

}
