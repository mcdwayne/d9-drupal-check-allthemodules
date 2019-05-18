# External entities CARTO

This module This module provides anew plugin for External Entities module that allows to connect to CARTO datasets and use any CARTO item as a Drupal entity.

This module allows to perform any CRUD operation against a given CARTO dataset.

## Requirements
* CARTO account and API Key
* Apply patch provided in [#2881058](https://www.drupal.org/node/2881058)

## Steps to set up a remote CARTO Dataset
1. Visit /admin/structure/external-entity-types and create a new External entity type
2. Set the human name, machine name and check whether you want your CARTO entities read only or not
3. In *Field mappings* vertical tab:
 * Set **cartodb_id** as the **External entity ID**
4. In *Storage settings* vertical tab:
 * Select **CARTO** Storage client
 * Select **json** format
 * Enter the CARTO dataset name as **Endpoint**
5. In *Pager settings* vertical tab:
 * Enter the desired number of items per page
 * Select **Starting item** Page parameter type
 * Select **Number of items per page** Page size parameter type
6. In *API key settings* vertical tab:
 * Enter the CARTO user name as **Header name**
 * Enter the CARTO Api key as **API key**
7. Leave empty *Parameters* vertical tab
8. Save the new External entity type
## Mapping the CARTO Geom field
1. Follow the steps above
2. Add a new **Geofield** field to the external entity type
3. Go to the edit external entity type page
4. Add field mapping **the_geom** to the new Geofield created above
