<?php
namespace Drupal\say_hello_dialogflow\Form;

use Drupal\Component\Utility\Random;
use Drupal\Core\Archiver\Annotation\Archiver;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\menu_ui\Form\MenuLinkEditForm;

use Drupal\say_hello_dialogflow\SayHelloDialogflow as SayHelloDialogflowService;
use Drupal\system\Entity\Menu;

/**
 * ExportModalForm class.
 */
class ExportModalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'modal_form_say_hello_dialogflow_modal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $options = NULL) {
    $form['#prefix'] = '<div id="modal_say_hello_dialogflow_form">';
    $form['#suffix'] = '</div>';

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate Agent export file'),
      '#attributes' => [
        'class' => [
          'use-ajax',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'submitModalFormAjax'],
        'event' => 'click',
      ],
    ];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $form['#attached']['library'][] = 'menu_ui/drupal.menu_ui.adminforms';
    $config = $this->config('say_hello_dialogflow.dialogflow_menu');
    $menu = $config->get('dialogflow_menu');

    $menu = str_replace(':','',$menu);
    if($menu) {
      $tree = \Drupal::menuTree()->load($menu, new MenuTreeParameters());
      // We indicate that a menu administrator is running the menu access check.
      $this->getRequest()->attributes->set('_menu_admin', TRUE);
      $manipulators = [
        ['callable' => 'menu.default_tree_manipulators:checkAccess'],
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ];
      $tree = \Drupal::menuTree()->transform($tree, $manipulators);
      $this->getRequest()->attributes->set('_menu_admin', FALSE);

      // Determine the delta; the number of weights to be made available.
      $count = function(array $tree) {
        $sum = function ($carry, MenuLinkTreeElement $item) {
          return $carry + $item->count();
        };
        return array_reduce($tree, $sum);
      };
      $delta = max($count($tree), 50);

      $form['links'] = [
        '#type' => 'table',
        '#theme' => 'table__menu_overview',
        '#header' => [
          $this->t('Menu link'),
          $this->t('Voice command'),
          $this->t('Callback response')
        ],
        '#attributes' => [
          'id' => 'menu-overview',
        ]
      ];

      $form['links']['#empty'] = $this->t('There are no menu links yet');

      $links=[];
      foreach ($tree as $element) {

        /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
        $link = $element->link;
        if ($link) {
          $id = 'menu_plugin_id:' . $link->getPluginId();
          $links[$id]['#item'] = $element;
          $links[$id]['#attributes'] = $link->isEnabled() ? ['class' => ['menu-enabled']] : ['class' => ['menu-disabled']];
          $links[$id]['title'] = Link::fromTextAndUrl($link->getTitle(), $link->getUrlObject())->toRenderable();
          if (!$link->isEnabled()) {
            $links[$id]['title']['#suffix'] = ' (' . $this->t('disabled') . ')';
          }
          // @todo Remove this in https://www.drupal.org/node/2568785.
          elseif ($id === 'menu_plugin_id:user.logout') {
            $links[$id]['title']['#suffix'] = ' (' . $this->t('<q>Log in</q> for anonymous users') . ')';
          }
          // @todo Remove this in https://www.drupal.org/node/2568785.
          elseif (($url = $link->getUrlObject()) && $url->isRouted() && $url->getRouteName() == 'user.page') {
            $links[$id]['title']['#suffix'] = ' (' . $this->t('logged in users only') . ')';
          }

          $links[$id]['voice_command'] = [
            '#type' => 'textfield',
            '#delta' => $delta,
            '#size' => '30',
            '#default_value' => 'Open '.$link->getTitle() . ' Page',
            '#title' => $this->t('voice command for the menu item @item', ['@item' => $link->getTitle()]),
            '#title_display' => 'invisible',
            '#attributes' => ['disabled' => 'disabled']
          ];

          $links[$id]['command_callback'] = [
            '#type' => 'textfield',
            '#delta' => $delta,
            '#size' => '30',
            '#default_value' => 'dialogflow_open_'.str_replace(' ','',strtolower($link->getTitle())).'_page',
            '#title' => $this->t('command callback for the menu item @item', ['@item' => $link->getTitle()]),
            '#title_display' => 'invisible',
            '#attributes' => ['disabled' => 'disabled']
          ];

          $links[$id]['id'] = [
            '#type' => 'hidden',
            '#value' => $link->getPluginId()
          ];
          $links[$id]['parent'] = [
            '#type' => 'hidden',
            '#default_value' => $link->getParent()
          ];

        }
      }

      foreach (Element::children($links) as $id) {
        if (isset($links[$id]['#item'])) {
          $element = $links[$id];

          $form['links'][$id]['#item'] = $element['#item'];

          // TableDrag: Mark the table row as draggable.
          $form['links'][$id]['#attributes'] = $element['#attributes'];
//          $form['links'][$id]['#attributes']['class'][] = 'draggable';

          // TableDrag: Sort the table row according to its existing/configured weight.
          $form['links'][$id]['#weight'] = $element['#item']->link->getWeight();

          // Add special classes to be used for tabledrag.js.
          $element['parent']['#attributes']['class'] = ['menu-parent'];
          $element['weight']['#attributes']['class'] = ['menu-weight'];
          $element['id']['#attributes']['class'] = ['menu-id'];

          $form['links'][$id]['title'] = [
            [
              '#theme' => 'indentation',
              '#size' => $element['#item']->depth - 1,
            ],
            $element['title'],
          ];

          $form['links'][$id]['voice_command'] = [
            [
              '#theme' => 'indentation',
              '#size' => $element['#item']->depth - 1,
            ],
            $element['voice_command'],
          ];

          $form['links'][$id]['command_callback'] = [
            [
              '#theme' => 'indentation',
              '#size' => $element['#item']->depth - 1,
            ],
            $element['command_callback'],
          ];

          $form['links'][$id]['id'] = $element['id'];
          $form['links'][$id]['parent'] = $element['parent'];
        }
      }

    }

    return $form;
  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#modal_say_hello_dialogflow_form', $form));
    } else {
      $dialogflow_service = \Drupal::getContainer()->get('say_hello_dialogflow.dialogflow');

      if(!empty($dialogflow_service->getConfig()->get('dialogflow_export_filename'))) {
        $last_export_path = \Drupal::service('file_system')->realpath(
          $dialogflow_service->getConfig()->get('dialogflow_export_filename')
        );
        $dialogflow_service->getEditableConfig()->set('dialogflow_export_filename', '')->save();
        unlink($last_export_path);
      }

      $export_path = 'private://dialogflow_export';
      if(!is_dir(\Drupal::service('file_system')->realpath($export_path))) {
        \Drupal::service('file_system')->mkdir($export_path, $mode = NULL, $recursive = FALSE, $context = NULL);
      }

      $update_time = time();

      if (\Drupal::getContainer()->has('stream_wrapper.private') && is_dir(\Drupal::service('file_system')->realpath('private://dialogflow_export'))) {

        $randomizer = new Random();
        $export_file_name = 'export-'.$randomizer->word(15).'.zip';
        $export_file_uri = $export_path.'/'.$export_file_name;
        $zip_path = \Drupal::service('file_system')->realpath($export_path);
        $zip = new \ZipArchive();
        $zip->open($zip_path.'/'.$export_file_name, constant("ZipArchive::CREATE"));

        // default menu items objects present in intents/ dir
        $zip->addEmptyDir('intents');
        $object = new \stdClass();
        $object->name = 'Default Fallback Intent';
        $object->auto = false;
        $object->contexts = [];
        $object->responses = [];
        $intent_response = new \stdClass();
        $intent_response->resetContext = false;
        $intent_response->action = 'input.uknown';
        $intent_response->affectedContexts = [];
        $intent_response->parameters = [];
        $intent_response->messages = [];
        $message = new \stdClass();
        $message->type = 0;
        $message->lang = 'en';
        $message->speech = $dialogflow_service->getConfig()->get('dialogflow_default_intent_text');
        $intent_response->messages[] = $message;
        $object->responses[] = $intent_response;
        $object->priority = 500000;
        $object->webhookUsed = false;
        $object->webhookForSlotFilling = false;
        $object->lastUpdate = $update_time;
        $object->fallbackIntent = true;
        $object->events = [];
        $zip->addFromString('intents/Default Fallback Intent.json', json_encode($object));

        $menu_items = $dialogflow_service->getMenuItems(str_replace(':','', $dialogflow_service->getConfig()->get('dialogflow_menu')));
        foreach($menu_items as $menu_item) {
          $action_name = 'Open '.$menu_item['title'].' Page';

          // default objects in main zip dir
          $object = new \stdClass();
          $object->name = $action_name;
          $object->auto = true;
          $object->priority = 500000;
          $object->webhookUsed = false;
          $object->weebhookForSlotFilling = false;
          $object->lastUpdate = $update_time;
          $object->fallbackIntent = false;
          $object->contexts = [];
          $object->events = [];
          $object->responses = [];
          $intent_response = new \stdClass();
          $intent_response->resetContexts = false;
          $intent_response->action = $menu_item['callback'];
          $intent_response->affectedContexts = [];
          $intent_response->parameters = [];
          $intent_response->defaultResponsePlatforms = new \stdClass();
          $intent_response->speech = [];
          $intent_response->messages = [];
          $message = new \stdClass();
          $message->type = 0;
          $message->lang = 'en';
          $message->speech = $action_name;
          $intent_response->messages[] = $message;
          $object->responses[] = $intent_response;
          $zip->addFromString('intents/'.$action_name.'.json', json_encode($object));

          // default objects in main zip dir
          $objects = [];
          $object = new \stdClass();
          $object->isTemplate = false;
          $object->count = 0;
          $object->updated = $update_time;
          $object->data = [];
          $data_object = new \stdClass();
          $data_object->text = $action_name;
          $data_object->user_defined = false;
          $object->data[] = $data_object;
          $objects[] = $object;
          $zip->addFromString('intents/'.$action_name.'_usersays_en.json', json_encode($objects));
        }

        // default objects in main zip dir
        $object = new \stdClass();
        $object->description = '';
        $object->language = 'en';
        $object->disableInteractionLogs = false;
        $object->defaultTimezone = 'America/New_York';
        $object->isPrivate = false;
        $object->customClassifierMode = 'use.after';
        $object->mlMinConfidence = 0.2;
        $object->supportedLanguages = [];
        $object->onePlatformApiVersion = 'v1legacy';
        $object->googleAssistant = new \stdClass();
        $object->googleAssistant->googleAssistantCompatible = false;
        $object->googleAssistant->welcomeIntentSignInRequired = false;
        $object->googleAssistant->startIntents = [];
        $object->googleAssistant->systemIntents = [];
        $object->googleAssistant->endIntentIds = [];
        $object->googleAssistant->oAuthLinking = new \stdClass();
        $object->googleAssistant->oAuthLinking->required = false;
        $object->googleAssistant->oAuthLinking->grantType = 'AUTH_CODE_GRANT';
        $object->googleAssistant->voiceType = 'MALE_1';
        $object->googleAssistant->capabilities = [];
        $object->googleAssistant->protocolVersion = 'V1';
        $object->googleAssistant->isDeviceAgent = false;
        $object->webhook = new \stdClass();
        $object->webhook->available = false;
        $object->webhook->useForDomains = false;
        $object->webhook->cloudFunctionsEnabled = false;
        $object->webhook->cloudFunctionsInitialized = false;
        $zip->addFromString('agent.json', json_encode($object));

        $object = new \stdClass();
        $object->version = '1.0.0';
        $zip->addFromString('package.json', json_encode($object));
        $zip->close();

        $export_zip_uri = str_replace($_SERVER['DOCUMENT_ROOT'] . '/', '', \Drupal::service('file_system')->realpath($export_file_uri));
        $dialogflow_service->getEditableConfig()->set('dialogflow_export_filename', $export_file_uri)->save();
        // Get raw configuration data without overrides.
        $response->addCommand(new OpenModalDialogCommand('Success', '<a target="_blank" href="/'.$export_zip_uri.'">Download Dialogflow Agent</a>', ['width' => 800]));
      } else {
        // Get raw configuration data without overrides.
        $response->addCommand(new OpenModalDialogCommand('Failure', '<a target="_blank" href="/admin/config/media/file-system">Please configure private file system path first</a> and make it writtable.</br>Export directory could not be saved: private://dialogflow_export', ['width' => 800]));
      }

    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['config.say_hello_dialogflow'];
  }

}