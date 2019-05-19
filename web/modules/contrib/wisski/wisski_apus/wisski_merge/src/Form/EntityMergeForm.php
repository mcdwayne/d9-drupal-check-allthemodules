<?php

namespace Drupal\wisski_merge\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use Drupal\wisski_merge\Merger;
use Drupal\wisski_salz\AdapterHelper;

class EntityMergeForm extends ContentEntityForm {
  
  
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // Do not attach fields to the confirm form, see e.g. ContentEntityDeleteForm.
    // see also buildForm() below
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // We must not override form() but buildForm()!
    // Form fields defined in form() will be merge treated as entity fields 
    // which we do not want here. At the same time we must override from()
    $form = parent::buildForm($form, $form_state);

    $form['info'] = array(
      '#type' => 'details',
      '#title' => $this->t('Hints'),
      '#open' => TRUE,
    );
    $form['info']['info1'] = array(
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '<p>' . t('Here you can specify instances/objects that shall be merged with this instance/object.') . '</p>'
                . '<p>' . t("Open another browser window and go to the other instance that you want to merge. Copy its URL from the browser's address field and paste it into the text field below. Make sure that the pasted URL begins with 'http'. (in the browser's field the http may be hidden, but it should appear in the text field below!)") . '</p>'
                . '<p>' . t("You can specify multiple instances/objects that shall all be merged with this one. Just copy each URL in the text field below, one per line.") . '</p>',
    );
    
    $form['insts'] = array(
      '#type' => 'textarea',
      '#title' => 'Instances to be merged into this one',
      '#rows' => 10,
      '#resizable' => 'both',
    );

    $form['warning'] = array(
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => array('class' => "messages messages--warning"),
      '#value' => t('Warning: This action cannot be undone! Be careful!'),
    );
    
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Merge');
    // we don't need the delete link
    unset($actions['delete']);
    return $actions;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    
    $urls = explode("\n", $form_state->getValue('insts'));
    $from_eids = array();
#    \Drupal::logger('MERGE ')->debug('yay44: @yay', ['@yay' => serialize($urls)]);
    foreach ($urls as $url) {
      $url = trim($url);
      if ($url == '') {
        continue;
      }
      if (mb_substr($url, 0, 1) == '<' && mb_substr($url, -1, 1) == '>') {
        $url = mb_substr($url, 1, mb_strlen($url) -2);
      } elseif (mb_substr($url, 0, 7) !== 'http://' && mb_substr($url, 0, 8) !== 'https://') {
        drupal_set_message(t("Skipping malformed URI: %l. If you know it is right, enclose it in %b", array("%l" => $url, "%b" => "<>")), 'error');
        continue;
      }
#      \Drupal::logger('MERGE ')->debug('yay2223: @yay', ['@yay' => serialize($url)]);
      $eid = AdapterHelper::extractIdFromWisskiUri($url);
#      \Drupal::logger('MERGE ')->debug('yay222: @yay', ['@yay' => serialize($eid)]);
      $from_eids[] = $eid;
    }
    
    $merger = new Merger();
    $status = $merger->mergeEntities($from_eids, $this->entity->id());
    if ($status === TRUE) {
      drupal_set_message($this->t('Successfully merged entities'));
    }
    else {
      drupal_set_message($this->t('Could not merge entities: @e', array('@e' => $status)), 'error');
    }
  }
}

