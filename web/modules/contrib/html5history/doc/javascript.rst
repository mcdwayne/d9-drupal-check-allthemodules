Client-side Control: Javascript API
=================================================

Instead of directly interacting with the ``window.history`` object, you should
instead use the ``Drupal.html5history`` object.

Add the Library
#################################################
To include the javascript library, your javascript should include the Durpal
libraries dependency:

.. code:: json

  mylibrary:
    js:
      js/my.js: {}
    dependencies:
      - html5history/html5history.proxy

Alter the history state
#################################################

.. code:: javascript

  Drupal.html5history.pushState(null, null, '/helloworld')

React to State Changes
#################################################

By default, the HTML5 History API only gives you a ``popstate`` event, but this
doesn't let you tune in when a ``pushState`` or ``replaceState`` call alters the
history stack.

See:
https://stackoverflow.com/questions/10940837/history-pushstate-does-not-trigger-popstate-event

To remedy this we provide custom events through the ``HistoryProxy`` object.

Listening for these events events does not require
``html5history/html5history.proxy`` to be included on the page (though
`triggering the custom events does`). It simply uses native
``addEventListener`` method.

All events are triggered on the ```window``` object.

The ``changestate`` event is particularly useful, and is fired whenever the
browser's current history stack context changes.

.. code:: javascript

  window.addEventListener('changestate', function(evt) {
    console.log(evt);
  });

Available Methods / Events
#################################################

All methods available on the
`HTML5 History Interface <https://www.w3.org/TR/2011/WD-html5-20110113/history.html#the-history-interface>`_.
are also available on `Drupal.html5history`.

The major difference, and the reason the proxy exists, is that the proxy also
emits custom events that the standard `History` object does not.

+-------------------------------------+-----------------------------------+------------+
| Method                              | Events Emitted                    | Target     |
+=====================================+===================================+============+
| ``back()``                          | ``popstate``, ``changestate``     | ``window`` |
+-------------------------------------+-----------------------------------+------------+
| ``forward()``                       | ``popstate``, ``changestate``     | ``window`` |
+-------------------------------------+-----------------------------------+------------+
| ``go(number)``                      | ``popstate``, ``changestate``     | ``window`` |
+-------------------------------------+-----------------------------------+------------+
| ``pushState(state, title, url)``    | ``pushstate``, ``changestate``    | ``window`` |
+-------------------------------------+-----------------------------------+------------+
| ``replaceState(state, title, url)`` | ``replacestate``, ``changestate`` | ``window`` |
+-------------------------------------+-----------------------------------+------------+

All events follow the
`popstate event <https://developer.mozilla.org/en-US/docs/Web/Events/popstate>`_
format.
