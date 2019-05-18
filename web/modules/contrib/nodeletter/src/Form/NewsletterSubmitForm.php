<?php

/**
 * @file
 * Contains \Drupal\nodeletter\From\NewsletterSubmitForm.
 */

namespace Drupal\nodeletter\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\nodeletter\Entity\NodeletterSendingInterface;
use Drupal\nodeletter\NodeletterSendException;
use Drupal\nodeletter\NodeletterService;
use Drupal\nodeletter\SendingStatus;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;


class NewsletterSubmitForm extends FormBase {



  /** @var NodeletterService */
  protected $nodeletterService;

  /**
   * Class constructor.
   *
   * @param \Drupal\nodeletter\NodeletterService $nodeletterService
   */
  public function __construct(NodeletterService $nodeletterService) {
    $this->nodeletterService = $nodeletterService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @noinspection PhpParamsInspection */
    return new static($container->get('nodeletter'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nodeletter_newsletter_submit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Node $node=NULL) {

    $node_type = $this->getNode($form_state)->getType();
    $sender = $this->nodeletterService->getNodeletterSender($node_type);
    $settings = $this->nodeletterService->getNodeletterSettings($node_type);
    $list_id = $settings->getListID();
    $template_id = $settings->getTemplateName();
    $nodeletter_config = \Drupal::config('nodeletter.settings');

	  $title = $this->t(
		  'Newsletter: %node_title',
		  ['%node_title' => $node->getTitle()],
		  ['context' => 'Nodeletter submit title']
	  );
	  $form['title'] = [
		  '#markup' => "<h1>$title</h1>",
	  ];

	  $list_label = 'unknown';
    if (empty($list_id)) {
      $msg_vars = [
        '@node_type' => $node_type,
        '@config_url' => Url::fromRoute("nodeletter.node_type_settings", ["node_type" => $node_type]),
      ];
      drupal_set_message($this->t("No recipient list set for content type @node_type. Please <a href=\"@config_url\">check the Nodeletter configuration for @node_type.</a>", $msg_vars), 'error');
      $recipientSelectors = [];
    } else {
      $recipientSelectors = $sender->getRecipientSelectors($list_id);
      $lists = $sender->getRecipientLists();
      foreach($lists as $list) {
        if ($list->getId() == $list_id) {
          $list_label = $list->getLabel();
          break;
        }
      }
    }

    $template_label = 'unknown';
    if (empty($template_id)) {
      $msg_vars = [
        '@node_type' => $node_type
      ];
      drupal_set_message($this->t("No newsletter template set for content type @node_type. Please <a href=\"@config_url\">check the Nodeletter configuration for @node_type.</a>", $msg_vars), 'error');
    } else {
      $templates = $sender->getTemplates();
      foreach($templates as $tpl) {
        if ($tpl->getId() == $template_id) {
          $template_label = $tpl->getLabel();
        }
      }
    }

    if (!empty($list_id) && !empty($template_id)) {
      $form['group_tabs'] = array(
        '#type'         => 'horizontal_tabs',
        '#group_name'   => 'group_tabs',
        '#entity_type'  => 'node',
        '#bundle'       => $node_type,
      );

      if (!empty($nodeletter_config->get('nodeletter_allow_sending'))) {
        $form['real'] = array(
            '#type'  => 'details',
            '#title' => t('Newsletter sending'),
            '#group' => 'group_tabs',
        );
      } else {
        $form['real'] = array(
            '#type'  => 'details',
            '#title' => t('Newsletter sending'),
            '#group' => 'group_tabs',
            '#disabled' => TRUE,
        );
        $form['real']['real_disabled'] = [
            '#type' => 'item',
            '#title' => $this->t('Attention!'),
            '#description' => $this->t('Newsletter sending is <strong>disabled</strong> in Nodeletter configuration.'),
        ];
      }



      $form['test'] = array(
        '#type'  => 'details',
        '#title' => t('Newsletter test mail'),
        '#group' => 'group_tabs',
      );

      $form['real']['recipient_list'] = [
        '#type' => 'item',
        '#title' => $this->t('Recipient List'),
        '#description' => $list_label,
      ];

      if (!empty($recipientSelectors)) {

        $recipient_options = [];
        foreach($recipientSelectors as $selector) {
          $recipient_options[ $selector->getId() ] = $selector->getLabel();
        }

        $form['real']['recipient_selectors'] = [
          '#type' => 'select',
          '#title' => $this->t('Limit Recipients in List'),
          '#description' => $this->t('If empty newsletter will be sent to all recipients in the newsletter list @list.', ['@list' => $list_label]),
          '#options' => $recipient_options,
          '#multiple'    => TRUE,
        ];
      }

      $form['template'] = [
        '#type' => 'item',
        '#title' => $this->t('Newsletter template'),
        '#description' => $template_label,
      ];

      $form['test']['test_recipient'] = [
        '#type' => 'email',
        '#title' => $this->t("Recipient address of test mail"),
        '#description' => $this->t("This address is only required and used for" .
          " test mails, not for newsletter submissions."),
        '#required' => FALSE,
      ];

      $form['comment'] = [
        '#type' => 'textarea',
        '#title' => $this->t("Comment for sending history"),
        '#description' => $this->t("This comment is optional."),
        '#required' => FALSE,
      ];

      $form['real']['actions'] = [
        '#type' => 'actions',
      ];

      $form['test']['actions'] = [
        '#type' => 'actions',
      ];

      $form['real']['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Submit newsletter sending'),
        '#name' => 'send-newsletter',
        '#button_type' => 'danger',
      ];

      $form['test']['actions']['test'] = [
        '#type' => 'submit',
        '#value' => t('Send test mail'),
        '#name' => 'send-test',
        '#button_type' => 'default',
      ];

    }



    $query = \Drupal::entityQuery('nodeletter_sending');
    $query->condition('node_id', $node->id());
    $query->sort('changed', 'DESC');
    $sending_ids = $query->execute();

    $storage = \Drupal::entityTypeManager()->getStorage('nodeletter_sending');
    /** @var NodeletterSendingInterface[] $sendings */
    $sendings = $storage->loadMultiple($sending_ids);

    if (empty($sendings)) {
      $msg_vars = [
        '@node_type' => $node_type
      ];
      $desc = $this->t('This @node_type has no past sendings recorded.',
        $msg_vars);
      $form['history'] = [
        '#type' => 'item',
        '#title' => $this->t('No sending history available'),
        '#description' => $desc
      ];
    } else {
      $form['history'] = [
        '#type' => 'details',
        '#title' => $this->t('Sending history'),
        '#open' => FALSE,
      ];

      $form['history'][] = [
        '#type' => 'nodeletter_sending_list',
        '#sendings' => $sendings,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#name'] == 'send-test') {
      $recipient = trim($form_state->getValue('test_recipient'));
      if (empty($recipient)) {
        $form_state->setErrorByName('test_recipient', $this->t("Recipient " .
          "address is required for test mail sending"));
      }
    } else if ($triggering_element['#name'] == 'send-newsletter') {
      $node = $this->getNode($form_state);
      $settings = $this->nodeletterService->getNodeletterSettings($node->getType());
      $sender = $this->nodeletterService->getNodeletterSender($node->getType());
      $available_selectors = $sender->getRecipientSelectors($settings->getListID());
      $submitted_selector_ids = $form_state->getValue('recipient_selectors');
      $validated_selectors = [];
      if (!empty($submitted_selector_ids)) {
        foreach($submitted_selector_ids as $submitted_selector_id) {
          $match = FALSE;
          foreach($available_selectors as $available_selector) {
            if ($submitted_selector_id == $available_selector->getId()) {
              $validated_selectors[] = $available_selector;
              $match = TRUE;
              break;
            }
          }
          if (! $match) {
            $error_msg = $this->t("Invalid selection");
            $form_state->setErrorByName('recipient_selectors', $error_msg);
            break;
          }
        }
      }
      $form_state->setValue('recipient_selectors', $validated_selectors);
    } else {
      $form_state->setErrorByName('');
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, Node $node=NULL) {
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#name'] == 'send-test') {

      $node = $this->getNode($form_state);
      $recipient = trim($form_state->getValue('test_recipient'));
      $sending = $this->nodeletterService->sendTest($node, $recipient);

      $comment = trim($form_state->getValue('comment'));
      if (!empty($comment)) {
        $sending->setComment($comment);
        $sending->save();
      }

      if ($sending->getSendingStatus() == SendingStatus::FAILED) {
        drupal_set_message(
          $this->t("Test mail for %node sending failed.", [
            '%node' => $node->getTitle(),
          ]),
          "error"
        );
        $error = NodeletterSendException::describe($sending->getErrorCode());
        $msg = $sending->getErrorMessage();
        drupal_set_message("$error: $msg","error");
      } else {
        drupal_set_message(
          $this->t("Test mail for %node sent to %mail.", [
            '%node' => $node->getTitle(),
            '%mail' => $sending->getTestRecipient()
          ])
        );
      }

    } else if ($triggering_element['#name'] == 'send-newsletter') {

      $node = $this->getNode($form_state);
      $recipient_selectors = $form_state->getValue('recipient_selectors');
      if (!empty($recipient_selectors)) {
        $recipient_selectors = array_values($recipient_selectors);
      } else {
        $recipient_selectors = [];
      }
      $sending = $this->nodeletterService->sendNewsletter($node,
        $recipient_selectors);

      $comment = trim($form_state->getValue('comment'));
      if (!empty($comment)) {
        $sending->setComment($comment);
        $sending->save();
      }

      if ($sending->getSendingStatus() == SendingStatus::FAILED) {
        drupal_set_message(
          $this->t("Newsletter sending %node failed.", [
            '%node' => $node->getTitle(),
          ]),
          "error"
        );
        $error = NodeletterSendException::describe($sending->getErrorCode());
        $msg = $sending->getErrorMessage();
        drupal_set_message("$error: $msg","error");
      } else {
        drupal_set_message(
          $this->t("Newsletter sending %node submitted.", [
            '%node' => $node->getTitle(),
          ])
        );
      }

    } else {
      drupal_set_message($this->t('Invalid submission type'));
      $form_state->setRebuild();
    }
  }


  /**
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\node\NodeInterface
   * @throws \Exception
   */
  protected function getNode(FormStateInterface $form_state) {

    $args = $form_state->getBuildInfo()['args'];
    if (empty($args) || ! $args[0] instanceof NodeInterface) {
      throw new \Exception("Invalid Form BuildInfo argument");
    }
    return $args[0];
  }
}
