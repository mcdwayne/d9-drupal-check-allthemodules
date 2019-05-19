# Term Node

Allow a term to be configured to show the content on a referenced node rather than the default term view.

 - From a Term, in any taxonomy, I can select a node
 - When I have selected a node. when I visit the term page, the node is displayed on the term url
 - Content on the page comes from the Node
 - Meta data is loaded from the Node
 - When I visit the Node the `rel="canonical"` is set as the term url
 - When the node appears anywhere else on the site e.g. homepage, other term pages - the term url is used

## Installation

Once enabled, a new field `field_term_node` will be available for adding to a taxonomy.
- Use `Manage Fields` on a taxonomy to add the `field_term_node` field.
- Edit any term within that taxonomy 
and use the reference lookup to choose a node that is to be used as the content for the term page.
