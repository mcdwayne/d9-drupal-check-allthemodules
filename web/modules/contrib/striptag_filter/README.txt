This module adds a new filter to the Drupal text formats.

This filter enables the user (admin) to strip specified HTML tags from the HTML text that uses this filter.

This is mainly intended for use with a "Full HTML" like format, but still wanting to have some html tags to be filtered
out. If you choose the Drupal "Limit allowed HTML tags .." filter and want to give the user nearly the "freedom"
of "Full HTML" like editing this can be quite complicated and at some point even impossible. So this is where
this module comes in and aims to give text format admins the possibility to give some trusted users
a "Full HTML" like format, but still limit some tags (like h1-tags, or script-Tags).

Security advisory:
This filter should not be used as a XSS protection filter, because this is not intended by this filter.
It does intenionally not use drupal XSS filter mechanisms. So text formats using this filter should only be given
to trusted roles.