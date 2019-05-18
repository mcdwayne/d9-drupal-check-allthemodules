html5history
=================================================

This module is for sites that want to use the
`HTML5 History Interface <https://www.w3.org/TR/2011/WD-html5-20110113/history.html#the-history-interface>`_,
but haven't fully committed to a heavier front end framework that manages the
history stack.

Using this module provides some key benefits over directly interacting with the
``window.history`` object:

  * It provides `custom events <javascript.html#react-to-state-changes>`_ that allow you to react to
    ``pushState`` and ``replaceState`` calls.
  * It provides `adapter classes for the Drupal Ajax API <ajax.html>`_ that
    allows you to control the history stack from the server side.
  * The implementation doesn't add much complexity over the native API.

Contents
----------

.. toctree::
   :maxdepth: 1

   ajax
   javascript
