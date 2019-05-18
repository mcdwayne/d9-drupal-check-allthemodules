<?php

namespace Drupal\homebox\Form;

use Drupal\Component\Utility\Random;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\homebox\Entity\HomeboxInterface;
use Drupal\homebox\Entity\HomeboxLayout;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class HomeboxPageForm.
 *
 * @ingroup homebox
 */
class HomeboxPageForm extends FormBase {

  /**
   * Current user account class.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * HomeboxPageForm constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user account entity.
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   Serializer service.
   */
  public function __construct(AccountInterface $account, Serializer $serializer) {
    $this->account = $account;
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Symfony\Component\Serializer\Serializer $serializer */
    $serializer = $container->get('serializer');
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $container->get('current_user');
    return new static(
      $account,
      $serializer
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'homebox_page';
  }

  /**
   * Save user settings.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function saveUserLayoutData(array $form, FormStateInterface &$form_state, Request $request) {
    $blocks = $form_state->getValue('blocks');

    if (!is_array($blocks)) {
      parse_str($form_state->getValue('blocks'), $blocks_value);
      $blocks = [];
      foreach ($blocks_value as $value) {
        parse_str($value, $block);
        // @todo Check block deleting in js. Possible there's parent element not remove.
        if ($block['id'] && $block['status']) {
          $blocks[] = $block;
        }
      }
    }

    $user = \Drupal::currentUser();

    $homebox = $request->attributes->get('homebox');
    $homebox_layout_storage = \Drupal::entityTypeManager()->getStorage('homebox_layout');
    $homebox_layout_id = $homebox_layout_storage->getQuery()
      ->condition('type', $homebox->id())
      ->condition('user_id', $user->id())
      ->execute();

    if (!empty($homebox_layout_id)) {
      $homebox_layout = $homebox_layout_storage->load(array_shift($homebox_layout_id));
    }
    else {
      // Create homebox layout entity for current user.
      $values = [
        'user_id' => $user->id(),
        'name' => $homebox->id(),
        'status' => 1,
        'type' => $homebox->id(),
      ];

      $homebox_layout = HomeboxLayout::create($values);
    }

    // Save user settings.
    // @todo: use dependency injection!!!
    $serializer = \Drupal::service('serializer');
    $data = $serializer->serialize($blocks, 'json');

    $homebox_layout->set('settings', $data);
    $homebox_layout->set('layout_id', $homebox->getRegions());
    $homebox_layout->save();
    return $form;
  }

  /**
   * Ajax callback to hide block buttons.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Response to hide buttons.
   */
  public static function hideBlockButtons() {
    $response = new AjaxResponse();
    $session = \Drupal::request()->getSession();
    $state = $session->get('block_buttons_visible');
    $response->addCommand(new CssCommand('.block_buttons', ['display' => $state ? 'none' : 'block']));
    $session->set('block_buttons_visible', !$state);
    return $response;
  }

  /**
   * Ajax callback to hide block.
   *
   * @param string $token
   *   Token of block.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Response to hide buttons.
   */
  public function closeBlock($token) {
    $response = new AjaxResponse();
    $response->addCommand(new RemoveCommand('#' . $token));
    return $response;
  }

  /**
   * Ajax callback to add block.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Response to hide buttons.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function addBlock(array $form, FormStateInterface $form_state, Request $request) {
    $user = \Drupal::currentUser();
    /* @var HomeboxInterface $homebox */
    $homebox = $request->attributes->get('homebox');

    // Get user layout settings or default homebox settings if not exists.
    /** @var \Drupal\homebox\Entity\HomeboxLayoutInterface $homebox_layout */
    $homebox_layout = self::getLayoutStorageData($homebox->id(), $user->id());
    $blocks = $homebox->getBlocks();
    if (isset($homebox_layout)) {
      $serializer = \Drupal::service('serializer');
      $layout_blocks = $homebox_layout->get('settings')->getValue();
      $layout_blocks = $serializer->decode(array_shift($layout_blocks)['value'], 'json');
    }
    else {
      $layout_blocks = $blocks;
    }

    $element = $form_state->getTriggeringElement();

    foreach ($blocks as $block) {
      if ($block['id'] == $element['#attributes']['block_id']) {
        break;
      }
      $block = NULL;
    }

    if (!empty($block)) {
      $block['status'] = TRUE;
      $layout_blocks[] = $block;

      /** @var \Drupal\Core\Block\BlockPluginInterface $block_instance */
      $block_instance = \Drupal::service('plugin.manager.block')->createInstance($block['id']);
      $token = \Drupal::csrfToken()->get((new Random())->string(32));
      $render[$block['id']] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => 'block_render',
          'id' => $token,
        ],
      ];

      $render[$block['id']]['render'] = $block_instance->build();
      $render[$block['id']]['render']['#theme'] = 'homebox_block';
      $render[$block['id']]['render']['#block_id'] = $block_instance->getPluginId();
      $render[$block['id']]['render']['#block_title'] = ($block['title'] != '' ? $block['title'] : $block_instance->label());
      $render[$block['id']]['render']['#block_content'] = $block_instance->build();

      $form['regions'][$block['region']][] = $render;
    }

    $form_state->setValue('blocks', $layout_blocks);

    self::saveUserLayoutData($form, $form_state, $request);

    return $form['regions'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, HomeboxInterface $homebox = NULL) {
    $is_ajax = $this->getRequest()->isXmlHttpRequest();
    if (!$is_ajax) {
      $session = \Drupal::request()->getSession();
      $session->set('block_buttons_visible', FALSE);
    }

    // Get user layout settings or default homebox settings if not exists.
    /** @var \Drupal\homebox\Entity\HomeboxLayoutInterface $homebox_layout */
    $homebox_layout = $this->getLayoutStorageData($homebox->id(), $this->account->id());
    $blocks_available_for_adding = $homebox->getBlocks();
    // Check if user layout settings exists and haven't been changed.
    if (isset($homebox_layout) && $homebox->getRegions() == $homebox_layout->get('layout_id')->getValue()[0]['value']) {
      $blocks = $homebox_layout->get('settings')->getValue();
      $blocks = $this->serializer->decode(array_shift($blocks)['value'], 'json');
    }
    else {
      $blocks = $blocks_available_for_adding;
    }

    // Get layout regions.
    /** @var \Drupal\Core\Layout\LayoutInterface $layout_instance */
    $layout_instance = \Drupal::service('plugin.manager.core.layout')->createInstance($homebox->getRegions(), []);

    $render = $regions = [];

    $form['#attached']['library'][] = 'homebox/draggable-blocks';
    $form['blocks'] = [
      '#type' => 'hidden',
    ];
    $form['add_block_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Add a block'),
      '#ajax' => [
        'callback' => 'Drupal\homebox\Form\HomeboxPageForm::hideBlockButtons',
        'event' => 'click',
      ],
    ];
    $form['save_form'] = [
      '#type' => 'button',
      '#value' => $this->t('Save'),
      '#attributes' => [
        'class' => ['homebox-save-form'],
      ],
      '#ajax' => [
        'callback' => 'Drupal\homebox\Form\HomeboxPageForm::saveUserLayoutData',
        'event' => 'click',
      ],
    ];
    $form['block_buttons'] = [
      '#type' => 'fieldset',
      '#attributes' => [
        'class' => ['block_buttons'],
        'style' => 'display: none;',
      ],
    ];

    // Create constant list of blocks available for adding on homebox page.
    foreach ($blocks_available_for_adding as $block) {
      /** @var \Drupal\Core\Block\BlockPluginInterface $block_instance */
      $block_instance = \Drupal::service('plugin.manager.block')->createInstance($block['id']);

      $form['block_buttons']['add_block_' . $block['id']] = [
        '#type' => 'button',
        '#value' => (isset($block['title']) && $block['title'] != '' ? $block['title'] : $block_instance->label()),
        '#attributes' => [
          'block_id' => $block['id'],
        ],
        '#ajax' => [
          'callback' => 'Drupal\homebox\Form\HomeboxPageForm::addBlock',
          'wrapper' => 'homebox',
          'event' => 'click',
        ],
      ];
    }

    foreach ($blocks as $block) {
      $block_instance = \Drupal::service('plugin.manager.block')->createInstance($block['id']);

      if ($block['status']) {
        $token = \Drupal::csrfToken()->get((new Random())->string(32));
        $render[$block['id']] = [
          '#type' => 'container',
          '#attributes' => [
            'id' => $token,
          ],
        ];

        $render[$block['id']]['render'] = $block_instance->build();
        $render[$block['id']]['render']['#theme'] = 'homebox_block';
        $render[$block['id']]['render']['#block_id'] = $block_instance->getPluginId();
        $render[$block['id']]['render']['#block_title'] = (isset($block['title']) && $block['title'] != '' ? $block['title'] : $block_instance->label());
        $render[$block['id']]['render']['#block_content'] = $block_instance->build();

        $regions[$block['region']][] = $render[$block['id']];
        $regions[$block['region']]['#attributes'] = [
          'id' => 'region_' . $block['region'],
          'class' => 'homebox-column ui-sortable',
        ];
      }
    }

    $plugin_definition = $layout_instance->getPluginDefinition();
    foreach ($plugin_definition->getRegions() as $id => $region) {
      if (!isset($regions[$id])) {
        $regions[$id] = [
          '#markup' => '',
          '#attributes' => [
            'id' => 'region_' . $id,
            'class' => 'homebox-column ui-sortable',
          ],
        ];
      }
    }

    $form['regions'] = $layout_instance->build($regions);
    $form['regions']['#attributes'] = ['id' => 'homebox'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Helper function to get the layout storage data.
   *
   * @param int $homebox
   *   Current homebox id.
   * @param int $user_id
   *   Current user id.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Layout homebox of current user.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getLayoutStorageData($homebox, $user_id) {
    $homebox_layout_storage = \Drupal::entityTypeManager()->getStorage('homebox_layout');
    $homebox_layout_id = $homebox_layout_storage->getQuery()
      ->condition('type', $homebox)
      ->condition('user_id', $user_id)
      ->execute();

    if (!empty($homebox_layout_id)) {
      $homebox_layout = $homebox_layout_storage->load(array_shift($homebox_layout_id));
    }
    else {
      $homebox_layout = NULL;
    }

    return $homebox_layout;
  }

}
