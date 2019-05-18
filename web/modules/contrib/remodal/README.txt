For a usage example check src/Controller/Devcontroller.php.
It contains a page with an ajax link that opens a remodal with another ajax
link (to demonstrate nested remodals), and a method that generates AjaxResponse
to be sent to the client.
To try it follow to /remodal/dev-page page after enabling the module.

Or you can just render a link to an existing route.
Here is an example for a site feedback form opened in remodal.

$content['remodal_link'] = array(
  '#type' => 'link',
  '#title' => $this->t('Feedback form'),
  '#url' => Url::fromRoute('entity.contact_form.canonical', ['contact_form' => 'feedback']),
  '#attributes' => [
    'class' => ['use-ajax'],
    'data-dialog-type' => 'remodal',
    'data-dialog-options' => Json::encode([
      'modifier' => 'custom-class-remodal',
    ]),
  ],
  '#attached' => array(
    'library' => array('core/drupal.ajax'),
  ),
);
