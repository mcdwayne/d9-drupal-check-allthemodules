<?php

namespace Drupal\tokendebug\Form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;

class TokenDebugForm extends FormBase {

  public function getFormId() {
    return 'tokendebug_form';
  }

  /**
   * Build the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text with tokens'),
      '#rows' => 1,
    ];

    $form['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => array_keys((array) $form_state->get('data')),
    ];

    $form['data'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Token data'),
      '#description' => $this->t('Token entity data like "node:17", each on one line.'),
      '#rows' => 3,
    ];

    $form['clear'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Clear unknown tokens'),
      '#description' => $this->t('Remove tokens that have not been replaced from the result text.'),
    ];

    $form['metadata'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show metadata'),
      '#description' => $this->t('Show bubblable metadata that is attached to the render array.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Validate the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $data_text = $form_state->getValue('data');
    $data = $this->parseData($data_text, $violations);
    foreach ($violations as $violation) {
      $form_state->setErrorByName('data', $violation);
    }
    $form_state->set('data', $data);
  }

  /**
   * Submits the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = $form_state->get('data');
    $text = $form_state->getValue('text');
    $clear = $form_state->getValue('clear');
    $show_metadata = $form_state->getValue('metadata');

    /** @var \Drupal\Core\Utility\Token $token_service */
    $token_service = \Drupal::service('token');
    $metadata = BubbleableMetadata::createFromRenderArray([]);
    try {
      $result = $token_service->replace($text, $data, ['clear' => $clear], $metadata);
    } catch (\Exception $e) {
      drupal_set_message(t('An exception was thrown:'));
      drupal_set_message($e);
    }
    if (isset($result)) {
      drupal_set_message(t('Successfully evaluated tokens:'));
      drupal_set_message(Markup::create($result));
    }

    if ($show_metadata) {
      // Like \Drupal\devel\Plugin\Devel\Dumper\DoctrineDebug::export
      $metadata_array = [];
      $metadata->applyTo($metadata_array);
      drupal_set_message(t('Added metadata:'));
      $dump = print_r($metadata_array, TRUE);
      drupal_set_message(Markup::create("<pre>$dump</pre>"));
    }

    $form_state->setRebuild();
  }

  /**
   * @param string $data_text
   * @param array $violations
   * @return array
   */
  protected function parseData($data_text, &$violations) {
    $violations = [];
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $lines = preg_split('/(\r\n?|\n)/u', $data_text);

    $data = [];
    foreach ($lines as $line) {
      $line = trim($line);
      if (!$line) {
        continue;
      }
      $parts = explode(':', $line, 2);
      $parts = array_map('trim', $parts);
      $parts += [1 => ''];
      list($type, $id) = $parts;
      try {
        $storage = $entityTypeManager->getStorage($type);
      } catch (PluginNotFoundException $e) {
        $violations[] = $this->t('No "%type" entity found.',
          ['%type' => $type]);
        continue;
      }
      $item = $storage->load($id);
      if (!$item) {
        $violations[] = $this->t('No "%type" entity with "id" %id found.',
          ['%type' => $type, '%id' => $id]);
        continue;
      }
      $data[$type] = $item;
    }
    return $data;
  }

}
