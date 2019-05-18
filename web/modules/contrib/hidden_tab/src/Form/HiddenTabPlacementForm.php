<?php

namespace Drupal\hidden_tab\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hidden_tab\Plugable\Komponent\HiddenTabKomponentPluginManager;
use Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginManager;
use Drupal\hidden_tab\Service\HiddenTabEntityHelper;
use Drupal\hidden_tab\Utility;
use Drupal\user\PermissionHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hidden Tab Placement add/edit form.
 *
 * This form redirects to layout page, as showing the placements collection
 * form is not much useful. placements are usually managed in their
 * corresponding page's layout form.
 *
 * @property \Drupal\hidden_tab\Entity\HiddenTabPlacementInterface $entity
 *
 * @see \Drupal\hidden_tab\Entity\HiddenTabPlacementInterface
 * @see \Drupal\hidden_tab\Entity\HiddenTabPageInterface
 */
class HiddenTabPlacementForm extends EntityForm {

  /**
   * Hidden Tab Page storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $pageStorage;

  /**
   * To get a list of all permissions for select list.
   *
   * @var \Drupal\user\PermissionHandler
   *
   * @see \Drupal\hidden_tab\Form\HiddenTabPageForm::form()
   */
  protected $userPermissionService;

  /**
   * To find komponents
   *
   * @var \Drupal\hidden_tab\Plugable\Komponent\HiddenTabKomponentPluginManager
   */
  protected $komponentMan;

  /**
   * To find templates
   *
   * @var \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginManager
   */
  protected $templateMan;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityStorageInterface $pageStorage,
                              PermissionHandler $userPermissionService,
                              HiddenTabKomponentPluginManager $komponent_man,
                              HiddenTabTemplatePluginManager $template_man) {
    $this->pageStorage = $pageStorage;
    $this->userPermissionService = $userPermissionService;
    $this->komponentMan = $komponent_man;
    $this->templateMan = $template_man;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('entity_type.manager')->getStorage('hidden_tab_page'),
      $container->get('user.permissions'),
      $container->get('plugin.manager.hidden_tab_komponent'),
      $container->get('plugin.manager.hidden_tab_template')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state, $save_redirect = NULL): array {
    if (!$this->entity->targetPageEntity()) {
      $form_state->setErrorByName('', $this->t('Target page missing, page id=@id', [
        '@id' => $this->entity->targetEntityId() ?: '?',
      ]));
      return $form;
    }
    elseif (!$this->entity->targetPageEntity()->template()) {
      $form_state->setErrorByName('', $this->t("Target page's template not set, page id=@id", [
        '@id' => $this->entity->targetEntityId(),
      ]));
      return $form;
    }
    elseif (!$this->templateMan->exists($this->entity->targetPageEntity()
      ->template())) {
      $form_state->setErrorByName('', $this->t("Target page's template plugin missing, page id=@id, template=@template", [
        '@id' => $this->entity->targetEntityId(),
        '@template' => $this->entity->targetPageEntity()->template(),
      ]));
      return $form;
    }

    $form['#attached']['library'][] = 'block/drupal.block.admin';
    $form['#attached']['library'][] = 'block/drupal.block';

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\hidden_tab\Entity\HiddenTabPlacement::load',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $o = [];
    foreach ($this->pageStorage->loadMultiple() as $page) {
      $o[$page->id()] = $page->label();
    }
    $form['target_hidden_tab_page'] = [
      '#type' => 'select',
      '#title' => $this->t('Page'),
      '#description' => $this->t('The page the placement belongs to.'),
      '#default_value' => $this->entity->targetPageId(),
      '#options' => $o,
      '#disabled' => TRUE,
    ];

    $template = $this->entity->targetPageEntity()->template();
    $form['region'] = [
      '#type' => 'select',
      '#title' => $this->t('Region'),
      '#description' => $this->t("The template's region in which the komponent will be placed."),
      '#default_value' => $this->entity->region(),
      '#options' => $this->templateMan->regionsOfTemplate($template),
      '#disabled' => TRUE,
    ];

    $form['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#description' => $this->t('Weight according to which the komponents in the selected region will be ordered.'),
      '#default_value' => $this->entity->weight(),
      '#maxlength' => 2,
    ];

    $form['view_permission'] = [
      '#type' => 'select',
      '#title' => $this->t('Permission'),
      '#description' => $this->t('Permission required to view the komponent.'),
      '#default_value' => $this->entity->viewPermission(),
      '#options' =>
        Utility::permissionOptions($this->userPermissionService->getPermissions()),
    ];

    $form['komponent_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Komponent Type'),
      '#default_value' => $this->entity->komponentType(),
      '#options' => $this->komponentMan->pluginsForSelectElement(),
      '#disabled' => TRUE,
    ];

    if ($this->komponentMan->exists($this->entity->komponentType())) {
      $form['komponent'] = [
        '#type' => 'select',
        '#title' => $this->t('Komponent'),
        '#default_value' => $this->entity->komponent() ?: 'frontpage::feed_1',
        '#options' => $this->komponentMan->komponentsOfPlugin($this->entity->komponentType()),
      ];
    }
    else {
      $form['komponent'] = [
        '#type' => 'select',
        '#title' => $this->t('Komponent'),
        '#default_value' => '',
        '#disabled' => TRUE,
        '#options' => [
          '' => t('Komponent is missing: @komponent'),
          [
            '@komponent' => $this->entity->komponentType(),
          ],
        ],
      ];
    }

    $form['komponent_configuration'] = [
      '#type' => 'value',
      '#default_value' => $this->entity->komponentConfiguration(),
    ];

    $form['extra'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Extra Constraints'),
    ];
    $form['extra']['target_user'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#title' => $this->t('User'),
    ];
    $form['extra']['target_entity'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => $this->t('Target Entity'),
    ];
    $form['extra']['target_entity_type'] = [
      '#type' => 'select',
      '#options' => ['node' => $this->t('Node')],
      '#default_value' => 'node',
      '#required' => TRUE,
      '#title' => $this->t('Target Entity Bundles'),
    ];
    $form['extra']['target_entity_bundle'] = [
      '#type' => 'select',
      '#options' => HiddenTabEntityHelper::nodeBundlesSelectList(TRUE),
      '#required' => FALSE,
      '#default_value' => '',
      '#title' => $this->t('Target Entity Bundle'),
    ];

    $form['lredirect'] = [
      '#type' => 'value',
      '#default_value' => $form_state->get('lredirect'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if (!$this->komponentMan->exists($form_state->getValue('komponent_type'))) {
      $form_state->setErrorByName('komponent', $this->t('Komponent type is missing: @komponent', [
        '@komponent' => $form_state->getValue('komponent_type'),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t("Created new Hidden Tab Placement %label.", $message_args)
      : $this->t("Updated Hidden Tab Placement %label.", $message_args);
    $this->messenger()->addStatus($message);

    $form_state->setRedirect(
      'entity.hidden_tab_page.layout_form',
      ['hidden_tab_page' => $this->entity->targetPageId()],
      [
        'query' => [
          'block-placement' => Html::getClass($this->entity->id()),
          'lredirect' => $form_state->getValue('lredirect'),
        ],
      ]
    );

    return $result;
  }

}
