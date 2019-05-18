This module provides the way how aggregation and compression can be
done by domain specific. Through this module one can select
the on / off feature for the aggregation and compression of css and js
for the specific domain. This module functionality will work only when
user do not select the BANDWIDTH OPTIMIZATION check boxes from 
the performance page of drupal. If the BANDWIDTH OPTIMIZATION 
are checked then this domain specific 
will not overide the drupal defaut feature.

## How it works
Download the module in the directory modules/contrib
where your contributed modules are placed.
Go to admin page configuration admin/config/domain/aggregate/{domain} page. 
Choose Aggregate from operations. 
Tick the checkboxes 
Aggregate and Compress css files,
Aggregate and Compress js files.
