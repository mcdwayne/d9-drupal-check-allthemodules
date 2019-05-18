Understanding the DependencyStack
=================================

The DependencyStack is intricately involved in both the import and export processes. It functions as an objet map of proxy objects to entities after they've been properly loaded. Usually it is invisible to the needs of the developer, however during various events (for example, the `tamper layer`_), entities are explicitly manipulated for tracking by the DependencyStack. The DependencyStack should always tracks entities by their remote uuid from the ContentHub Service.

.. _tamper layer: events.html#tampering-with-data

In general the DependencyStack is relatively simple. It holds ``\Drupal\depcalc\DependentEntityWrapperInterface`` objects, in an object map keyed by remote uuid. DependentEntityWrapper objects understand how to retrieve and entity, but are not themselves an entity. This means we can put an enormous number of objects into a DependencyStack and yet keep memory relatively low.

The DependencyStack is actually provided by the `DepCalc`_ module and further exploration of this topic should include technical exploration of the class itself and the greater whole of the DepCalc module.

.. _DepCalc: https://drupal.org/project/depcalc