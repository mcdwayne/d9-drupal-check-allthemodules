#!/bin/bash
#
# Rollback the migrations in order.
# This script is only for development.
#

MIGRATIONS='
import_product
import_paragraph_tab
import_paragraph_cta
import_paragraph_product_video
import_product_variation
import_attribute_value
import_image
import_taxonomy_term'

for MIGRATION in ${MIGRATIONS}
do
  drush mrs ${MIGRATION}
  drush mr ${MIGRATION}
done

#
# Remove test directory for source files.
#
#DST='../../../sites/default/files/images'
#rm -rf ${DST}
