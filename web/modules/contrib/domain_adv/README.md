#Drupal access advanced

This an extension to the [Domain](https://drupal.org/project/domain) module
which is used to create many domains from a single installation.

For what I see as a problem is that the domain module uses the node access
api's to determine which nodes are displayed on which site. This will work if
you only use Domain access, and no other access module like simple access, tac, or Organic groups.

Node access is more a per user where as domain access is per site. Now because
the domain access modules have such a good API, Domain Access Advanced is able
to turn off the node access integration in Domain access and re-implement it
as a query rewrite (which is how node access actually works) so that content
can only be accessed via the domains or affiliates that are specified.

This means that then if there is are node access modules they will then reduce
the list more to only allow the user to view the nodes they have access to.

