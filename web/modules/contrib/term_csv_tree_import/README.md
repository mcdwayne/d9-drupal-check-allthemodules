Introduction
============

This module import terms with sub elements and sub sub elements with their custom fields if any.

Install
=======

Just enable the module.

Usage
=====

Follow the format.

Parent column name should prefix 'Parent'.

Every sub element column name should prefix 'Child'.

Every custom filed should prefix 'Custom' and machine name of the field.

The arrangement of column should be 

1. Parent column and its custom field columns if any.
2. Child element column and its custom field columns if any.
3. Child's Child element column and its custom field columns if any.

It will be created as 

Parent 
   |
    ---->Child element
            |
             ---->Child element


Parent,Custom_field,Child,Custom_field,Child1,Custom_field

Make sure the column names should be unique.
Make sure all the lines should have same number of columns.


