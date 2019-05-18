### Group Forum

The Group Node module (a submodule of the Group module) supports permission
control for group forum nodes.  The Group Forum module adds the ability to make
forums private to a group or groups.

When you assign a root forum container to a group (or groups), forums and subforums
within that container will be private to the group(s).  You can assign
containers to any number of groups, so that more than one group can access a
container.  (Note: Only root containers matter for determining access, so moving
a container out of its root position in the taxonomy may permit access to the
container.)

Groups can either access all forums and subforums within a root, or none within
that root.  The module does not support restricting a group to specific forums
within a root, so structure your forum taxonomy accordingly.

Note that topic nodes themselves are still under the purview of the Group Node
module. It will be possible for a user to access a node -- if it is their
group content and you allow access via their Group Node permissions -- even if
they can't access the forum in which the node appears.

It is expected that in most cases Group Forum will be used in conjunction with
Group Node, allowing the site builder to control both forum topic permissions
and access to forum containers.

# Install

1. At admin/structure/forum, create one or more forum containers you want to
make private.  Add at least one forum to each of these containers.

2. Install the Group module if you have not done so already.  You will probably
also want to enable the Group Node module.

3. Create a group type and create a taxonomy reference field in your group
type.  By default, the Group Forum module expects this field to be named
'field_forum_containers' (machine name).  Configure the field to be unlimited to
allow multiple forum containers per group.

4. Use "Set available content" in your group type to make Forum Topic available
for your group type.  In the Group module, adjust the Group permissions as
desired to control access to forum topic nodes.

3. Create/edit some groups and use the taxonomy reference field to associate
each group with one or more of your forum containers.

4. Enable the Group Forum module and edit the module config if you are using
something different than 'field_forum_containers' for the taxonomy reference
field name.
