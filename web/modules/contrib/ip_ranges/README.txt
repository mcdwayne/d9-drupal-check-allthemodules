README.txt
==========

IP Ranges is a module that let's you completely ban both single IP-addresses as well as full ranges from your site. The ban is triggered on the request event, so stops requests as early as possible without wasting server resources.

You can also define whitelists that override blacklists, both single and ranged.
The UI is similar to core ip-ban, so you will feel at home immediately.


INSTALLATION
=============

Just enable the module as usual.


USAGE
============
After enabling the module, go to admin/config/people/ip-ranges to find a form with three elements:

- "IP range start / Single IP-address"
- "IP range end"
- "List type"

The first two fields take an IP-Address in the form of "a.b.c.d". If the second field is filled, the two will be treated as a range. If you leave it empty, the value from the first field only is used.

Type can be either "blacklist" or "whitelist", where blacklisted IP's are denied from the site, and whitelisted are allowed.

**Whitelists always override blacklists.**



