# Filterable Link

This module attempts to streamline the linking of internal content by 
defining what bundles the custom link field can reference. Do you have 
similarly named content that is crafted from different bundles? Then 
you'll notice it's hard to differentiate between them when referencing 
via the core link field. 

Wouldn't it be nice if you can specify which bundles an instance of the 
core link field can source? This module basically extends the core link 
field but it allows an admin to specify a list of target bundle types 
that ultimately filter the link's autocomplete responses.