<?php
namespace Drupal\pagedesigner_embed\Plugin\pagedesigner\Asset;

use Drupal\Core\Form\FormState;
use Drupal\pagedesigner\Plugin\pagedesigner\Asset\Standard;
use Drupal\views\Views;

/**
 * @PagedesignerAsset(
 *   id = "embed",
 *   name = @Translation("Embed asset"),
 *   types = {
 *      "embed",
 *   },
 * )
 */
class Embed extends Standard
{

    public function get($filter = [])
    {
        return [];
    }

    public function getSearchForm()
    {
        $view = Views::getView('embed');
        $view->setDisplay('asset');
        $view->initHandlers();
        $form_state = new FormState();
        $form_state->setFormState([
            'view' => $view,
            'display' => $view->display_handler->display,
            'exposed_form_plugin' => $view->display_handler->getPlugin('exposed_form'),
            'method' => 'get',
            'rerender' => true,
            'no_redirect' => true,
            'always_process' => true,
        ]);
        $form = \Drupal::formBuilder()->buildForm('Drupal\views\Form\ViewsExposedForm', $form_state);
        return $form;
    }

    public function getCreateForm()
    {
        $values = array('bundle' => 'image');
        $node = \Drupal::entityTypeManager()
            ->getStorage('media')
            ->create($values);
        $form = \Drupal::entityTypeManager()
            ->getFormObject('media', 'default')
            ->setEntity($node);
        return \Drupal::formBuilder()->getForm($form);
    }
}
