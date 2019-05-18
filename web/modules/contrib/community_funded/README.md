README.md
=========

This module allows you to use your user ID from 
CommunityFunded.com to display your campaign page via
a block.

<h3>To display your campaigns:</h3>
Download module and install as usual. Once enabled, 
the module will provide a custom block called 
"Community Funded." Place this block where you would 
like to display your campaigns.

Once placed, the block should display, "Currently 
empty" because we haven't input our Data User ID from
Community Funded yet.

Navigate to Configuration in the admin bar and under 
System you'll find "Community Funded Configuration." 
(<strong>admin/config/system/community-funded
</strong>)

In the text box, paste your User ID from Community 
Funded and click "Save configuration." Your campaigns 
should now display within the Community Funded block 
that you've placed.

<h3>Quick troubleshooting:</h3>
If your campaigns do not display:
<ul>
  <li>Check "Administer Community Funded" permission 
  (admin/people/permissions) </li>
  <li>Check the console for JS errors</li>
  <li>Check block placement and block 
  permissions/display options</li>
</ul>
