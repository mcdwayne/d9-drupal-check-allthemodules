Creating Your First Publisher
=============================

ContentHub attempts to drastically simplify your setup process by removing the vast majority of publisher configuration. This means that ContentHub acts much like a replication service. Rather than trying to guess or do "best guess" matches between source and destination, ContentHub communicates all necessary information to support content syndicated from a Publisher to a Subscriber. This means that ContentHub sends all your data by default, but instead of sending only the content, it also sends the configuration necessary to support that content.

This process includes, but is not limited to, your content bundles, field storage and configuration, view modes, form modes and much much more.

Getting Started
^^^^^^^^^^^^^^^

To get started simply visit the ``Extend`` page within the Drupal administration interface and enable the ``Acquia ContentHub Publisher`` module.

Once enabled, ContentHub will progressively add content to your export queue. This means every time you touch any entity within all of Drupal, it will be queued for export to the ContentHub Service. To quickly demo this functionality to yourself you can visit **Manage » Content**, check mark all the content and use the bulk update process to resave the content. This will update many of the nodes in your site and enqueue them for exporting.

Running the Queue
^^^^^^^^^^^^^^^^^

The queue is most easily run via Drush. While other methods could certainly be supported, the most reliable option for many reasons is Drush. Per the `ContentHub Requirements`_ you MUST have Drush 9.x. The command to run is as follows:

``drush queue:run acquia_contenthub_publish_export --uri=$siteUrl``

This will run the queue, calculating all necessary dependencies for your data and sending it to the ContentHub Service. The ``--uri=`` is a requirement of syndicating data of various types to your subscribers. Without it, subscribers cannot find the canonical representation of things like files for import.

Excluding Data from Being Enqueued
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Since the publisher, by default, exports all data, it may be that for various reasons (such as security and access concerns) that certain types of data on your site must never be enqueued for export. There's no user interface to support this process, but there is an API. Read more in the `developer docs`_.

It is important to note that the enqueue process is just one way in which data is identified for export. The dependency calculation process can and will calculate to items which would normally not result in a queue creation. In this case it is often indicative of potential architectural problem on the site in the first place, and must be rectified in the export process by a stronger utility. Read more about the `publish entities`_ API in the developer docs.

Beta Note
^^^^^^^^^

During the Beta release, ContentHub is including a Drupal Batch API solution for exporting your content. You must still enqueue it as described above, but instead of running Drush, you can use this administrative interface. It's found with the ContentHub settings and is only recommended for testing purposes. If you attempt a significant test that includes deeply nested dependency trees, the Drush based solution will always be suggested and preferable.

.. _ContentHub Requirements: install.html#requirements
.. _developer docs: development/publish/exclude.html#preventing-enqueuing
.. _publish entities: development/publish/exclude.html#preventing-entity-syndication