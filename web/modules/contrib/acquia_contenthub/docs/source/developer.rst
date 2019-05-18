Developing with ContentHub 2.x
==============================

ContentHub 2.x is a radical departure from the previous version of ContentHub. As such it has a number of capabilities that were not present in previous version of the product. The best way to begin understanding what ContentHub 2.x is attempting to do is to discuss the theory at the heart of its functionality.

Theory (Dependency Calculation)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

In order to properly syndicate content from one site to another, a proper understanding of the underlying data structure has to be established. In the confines of Drupal, that means introspecting a lot of data objects and chaining relationships we find within those objects (implicitly and explicitly) to other objects until we've calculated all related data. This is done via a new module called `Dependency Calculation`_, or "DepCalc" for short. DepCalc, does this with a relatively simple architecture that involves a single dispatched event. That event passes around an entity to all subscribers which take that entity, extract or relate data to it as is necessary and then rerun dependency calculation against any dependencies they may have added to the list.

As a developer, this means if you create a "non-standard" relationship between two entities or entity types, you'll need to define a dependency calculation event subscriber to tell Drupal about that relationship so that it can be properly syndicated from the publisher to the subscribers. So, what is a "non-standard" relationship? Well, any relationship that your code knows about but which is not represented by something like an entity reference field would likely be considered "non-standard". As an example, DepCalc has to manually calculate entities embedded by EntityEmbed in text areas. These are often media entities required for the proper syndication of the entity in which they are embedded, but there's no obvious hard link between them. You have to know it's there and react accordingly.

Theory (Serialization/Replication)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Often times when sending data from one site to another, we default to thinking in terms of REST or similar serialization processes. This is natural, but once you begin to unpack it, you will find that it is often the wrong tool for the job. As an example, using Drupal's normal REST resources will result in entities never syndicating when an unpublished translation is edited. This can result in data loss on subscriber sites if they misinterpret that as a complete removal of data, which is precisely how REST will represent it... as blank. This is true of any entity level access check. Similarly, field level access is typically reserved for preventing the viewers of a site from seeing some data. It is still often essential to the entity as a whole, and simply excluding it from the syndication process is not a safe default.

In order to solve these and many other problems, ContentHub 2.x thinks more in terms of replication than it does serialization. Of course there is still a serialization process of sorts, but it does not run through Drupal's normal process, nor does it even require those modules. Instead, ContentHub depends on a small set of field and entity level interpreter which can serialize and unserialize field level data in safe, multilingual, formats that are easy to turn back into normal entity data during import.

What it All Means
^^^^^^^^^^^^^^^^^

If you create a custom entity type, it's probably going to work within ContentHub by default (those "non-standard" relationships not withstanding). Likewise, if you need to create a custom field type, you should evaluate the default handlers that are in place for serializing and unserializing your field's data. ContentHub provides a few classes which will attempt to make sane guesses, but if they fail, a couple of relatively simple classes can set it all right. We will document that at length in the `field serialization`_ section.

.. toctree::
   :maxdepth: 1
   :caption: Developer Topics:

   development/events
   development/serialization
   development/dependencyStack
   development/publisher
   development/subscriber
   development/cdf

.. _Dependency Calculation: https://www.drupal.org/project/depcalc
.. _field serialization: development/serialization.html
