<?php

namespace Drupal\optit\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\optit\Optit\Optit;

/**
 * Defines a form that configures optit settings.
 */
class SendBulkSmsForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'optit_bulk_sms_form';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $keyword_id = NULL, $phone = NULL, $interest_id = NULL) {
    /** @var \Drupal\user\PrivateTempStore $tempstore */
    $tempstore = \Drupal::service('user.private_tempstore')->get('optit_bulk');
    if ($bulk = $tempstore->get('sms_messages')) {
      $form['bulk'] = [
        '#type' => 'value',
        '#value' => $bulk,
      ];
      $form['messages'] = $this->prepareMarkup($bulk);
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Send messages'),
      );
      $form['clear'] = array(
        '#type' => 'submit',
        '#value' => t('Clear the list'),
        '#submit' => ['::clearBulk'],
      );
    }
    else {
      drupal_set_message($this->t('There are not any SMS messages in the bulk.'), 'warning');
      $url = Url::fromRoute('optit.structure_keywords');
      $linkGenerator = $this->getLinkGenerator();

      $message = $this->t('Try going to %keywords page to create some messages and add them to the bulk.', [
        '%keywords' => $linkGenerator->generate($this->t('keywords'), $url),
      ]);

      $form['goto'] = [
        '#markup' => $message,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $optit = Optit::create();
    $success = $optit->messageBulkArray($form_state->getValue('bulk'));

    if ($success) {
      drupal_set_message($this->t('Bulk messages were sent successfully.'));
      $this->clearBulk();
      $form_state->setRedirect('optit.structure_keywords');
    }
    else {
      drupal_set_message($this->t('There was an error processing bulk messaging. Please check error logs for more information.'), 'error');
    }
  }


  /**
   * Remove all messages from the temporary storage.
   */
  function clearBulk() {
    /** @var \Drupal\user\PrivateTempStore $tempstore */
    $tempstore = \Drupal::service('user.private_tempstore')->get('optit_bulk');
    $tempstore->delete('sms_messages');
  }

  /**
   * Build a render array for a table containing all messages in the bulk.
   */
  function prepareMarkup($bulk) {
    $optit = Optit::create();

    // Start building vars for theme_table.
    $vars = array(
      '#theme' => 'table',
      '#header' => [
        t('Keyword'),
        t('Title'),
        t('Message'),
        t('Recipients'),
      ],
      '#rows' => []
    );

    foreach ($bulk as $keyword_id => $messages) {
      $keyword = $optit->keywordGet($keyword_id);
      $keywordName = $keyword->get('keyword_name');
      // Iterate through messages in a keyword.
      foreach ($messages as $message) {
        $messageTitle = $message['title'];
        $messageMessage = $message['message'];
        // @todo: If there are not any phones, write "no recipients found for this message."
        $phones = [
          '#theme' => 'item_list',
          '#items' => $message['phones']
        ];
        $vars['#rows'][] = array(
          $keywordName,
          $messageTitle,
          $messageMessage,
          'phones' => ['data' => $phones]
        );
      }
    }

    return $vars;
  }
}
