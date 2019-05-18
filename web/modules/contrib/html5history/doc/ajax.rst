Server-side Control: Issuing Ajax Commands
=================================================

For background, refer to the
`Drupal Ajax API documentation <https://api.drupal.org/api/drupal/core!core.api.php/group/ajax/8.2.x>`_.

Add the Library
#################################################

In order for the browser to process the ajax commands, you must add the
``html5history/html5history.ajax`` library to the page where ajax commands will
be issued:

.. code:: php

  $render_array['#attached']['library'][] = 'html5history/html5history.ajax';

.. note::

  Creating the ajax commands on the server side will not automatically include
  the client side js.

Issue AjaxResponse Commands
#################################################

To update the browser history in an ajax response:

.. code:: php

  use Drupal\Core\Ajax\AjaxResponse;
  use Drupal\html5history\Ajax\HistoryPushStateCommand;

  $response = new AjaxResponse();
  $response->addCommand(new HistoryPushStateCommand(NULL, NULL, '/helloworld');
  return $response

This example translates to roughly the following javascript code:

.. code:: javascript

  window.history.pushState(null, null, '/helloworld');

.. note::

  Commands and there arguments map directly to the arguments listed in the
  `javascript documentation <javascript.html#available-methods-events>`_.

The class names are:

+-------------------------------------+----------------------------------------------------------+
| Method                              | Class                                                    |
+=====================================+==========================================================+
| ``back()``                          | ``\Drupal\html5history\Ajax\HistoryBackCommand``         |
+-------------------------------------+----------------------------------------------------------+
| ``forward()``                       | ``\Drupal\html5history\Ajax\HistoryForwardCommand``      |
+-------------------------------------+----------------------------------------------------------+
| ``go(number)``                      | ``\Drupal\html5history\Ajax\HistoryGoCommand``           |
+-------------------------------------+----------------------------------------------------------+
| ``pushState(state, title, url)``    | ``\Drupal\html5history\Ajax\HistoryPushStateCommand``    |
+-------------------------------------+----------------------------------------------------------+
| ``replaceState(state, title, url)`` | ``\Drupal\html5history\Ajax\HistoryReplaceStateCommand`` |
+-------------------------------------+----------------------------------------------------------+
