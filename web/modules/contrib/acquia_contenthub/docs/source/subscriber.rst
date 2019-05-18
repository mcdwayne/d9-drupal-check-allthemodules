Creating Your First Subscriber
==============================

As with any ContentHub site, you must first `install ContentHub`_. Once completed, install the ``Acquia ContentHub Subscriber`` module from the **Extend** page within Drupal.

Just like the publisher, subscriber setup has been simplified by largely removing any sort of initial configuration options. This means that once configured and connected via webhook to your service, the subscriber will automatically enqueue data for import as it is exported by your publisher.

.. _install ContentHub: install.html

Running the Queue
^^^^^^^^^^^^^^^^^

Just like the publisher, the subscriber depends on a queue. The most reliable option for queue running is Drush. Per the `ContentHub Requirements`_ you MUST have Drush 9.x. The command to run is as follows:

.. _ContentHub Requirements: install.html#requirements

``drush queue:run acquia_contenthub_subscriber_import``

This queue will process entity groups sent by the ContentHub Service, determine any missing dependencies, retrieve them, and reduce the number of necessary import as the process continues until all the data necessary to support the syndicated entities is present. This means, at any point in the future, even new subscribers will get historic content and configuration required to support any entity syndicated to them.

Filtering Imports
^^^^^^^^^^^^^^^^^

Import filtering is done via the ContentHub Service with a mechanism we call `Cloud Filters`_. *Cloud Filters* are configured on your publisher and deployed to each individually registered webhook in your platform.

In order to learn more about *Cloud Filters* and how to enable them for your sites, refer to the documentation about `Managing Cloud Filters`_.

Beta Note
^^^^^^^^^

The initial import process does not yet exist, so until it does, you may need to manually resyndicate your content from the publisher regularly to get it to show up in your subscriber(s).



.. _Cloud Filters: filters.html
.. _Managing Cloud Filters: filters.html
