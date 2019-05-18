INTRODUCTION
------------

The Commerce License Entity Field module provides a License type which when
granted, sets a value on an entity field. The field and the value are configured
in the product, while the entity to change is chosen by the purchasing user.

The use case is for users purchasing some sort of upgrade to one of their
entities, such as the 'promoted' flag on a node, or a boolean field which
indicates the entity behaves in a certain way.

THIS MODULE IS INCOMPLETE! SEE BELOW!

TODO
----

This has been written to satisfy the specific needs of a project, and while it's
designed for generic use, the parts needed for that are not finished.

The following remains to be done to make it fully usable:

* Decide on a way to allow customers to select an entity in the license cart
  form. This could use EntityReferenceSelection plugins, but this has some
  problems that would need to be solved:
  * There is a use case for only allowing customers to select entities they own,
    so this module would need to provide some custom plugins.
  * The license_target_entity field on the license could not simply use a
    core entityreference widget, since the configuration for how to pick an
    entity is different *for every license entity*. Therefore we'd need some
    custom entity reference field widget which takes its selection plugin
    from another field on the license, rather than from the entity reference
    field's own configuration.
* Add the configuration options for the new system to the plugin.
* Fix and finish the form.
* Implement the license cart form.
