INTRODUCTION
------------

Form delegate is a better alternative for entity form alter hooks.
It provides a type of plugin, form delegates, which can alter all 
three methods of entity forms, by this making code better structured.

_The module currently provides plugins to alter entity forms only.
It is planned to be usable for all types of forms._

This module is provided solely for developers and has no use for end 
users as is. The documentation below assumes you have a basic knowledge 
and experience with the 
(core FORM API)
[https://www.drupal.org/docs/8/api/form-api/introduction-to-form-api].


FEATURES
--------

 * altering based on entity / bundle / operation / form display mode
 * direct access to the form entity
 * easy alteration prioritization: no more hook definition alters
 * form display mode selection based on context
 * possibility for prevention of base submit execution


REQUIREMENTS
------------

The module doesn't have any requirements. Also there are no third 
party dependencies. The only thing needed to make use of this module 
is forms and/or entities.


CONFIGURATION
-------------

There are no module specific configurations.


GETTING STARTED
---------------

First and foremost you should read through the annotation of the plugin
 (scr/Annotation/EntityFormDelegate.php). After that you can start
implementing plugins under **src/Plugin/Form**, as an example for the 
node article content type.


FORM DISPLAY MODES
------------------

The module provides an event called EntityFormInitEvent which is 
dispatched when the form is initializing. At this stage it **can be 
selected which display mode a form should use**. In certain cases it is 
useful to present the same form in multiple view modes, for example
an account editing form in different ways to the admins and the
other users. In this case create your view modes and implement the
event subscriber to select the form display mode depending on user
role.


MULTI-STEP FORMS
----------------

With entities and form display modes combined with the form delegates
it is possible to create multi-step forms. For each step before the
final step you can **set the delegate to prevent the original submit**.
Then in each non last step`s **submit** you should change the display 
mode on the form object obtained from the from state and set the form 
state to be rebuilt. This way values get stored on the form state, the
form is rebuilt, but with different fields shown / different widgets.


EXAMPLE DELEGATE PLUGIN 
-----------------------

```php
<?php

namespace Drupal\...\Plugin\Form;

use ...

/**
 * Alteration of article delegate.
 *
 * @EntityFormDelegate(
 *   id = "article_alter",
 *   entity = "node",
 *   bundle = "article",
 *   operation = {"default", "edit"}
 * )
 */
class ArticleFormDelegate extends EntityFormDelegatePluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $formState) {
    $form['title']['#required'] = FALSE;
    $form['title']['#access'] = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('title')) {
      $form_state->setErrorByName('title', 'Should not have value.');  
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    $this->getEntity()->setTitle('Article');
    drupal_set_message('Yeah you saved it!');
  }
  
}
```
