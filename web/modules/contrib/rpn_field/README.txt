This module allows you to execute any RPN calculation on the value of a field
when a hook fires. The module will act on every fieldable entity argument of
the hook and then traverse recursively any entity references. Then it will
check every field of the entities and check for the third party setting called
rpn_field. If one is found, the value is treated as an RPN notation
calculation. The RPN stack starts with the field value.

Currently, there is no nice UI. 

Let's say we want to give 1 point every time a node is inserted.

Create an integer field (in my example, it is on user) and then head over to
admin/config/development/configuration/single/export.

Export the field and add this:

third_party_settings:
  rpn_field:
    node_insert/uid: '1 +'

This means 

1. the first argument of hook node_insert. If it'd be the second, you'd need
node_insert/1

2. Using this argumemnt, the field uid points to our user. Longer chains are
possible.
