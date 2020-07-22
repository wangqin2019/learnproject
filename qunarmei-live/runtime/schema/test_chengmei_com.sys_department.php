<?php 
return array (
  'id_department' => 
  array (
    'name' => 'id_department',
    'type' => 'varchar(10)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => false,
  ),
  'id_parent' => 
  array (
    'name' => 'id_parent',
    'type' => 'varchar(10)',
    'notnull' => false,
    'default' => 'root',
    'primary' => true,
    'autoinc' => false,
  ),
  'st_department' => 
  array (
    'name' => 'st_department',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'st_address' => 
  array (
    'name' => 'st_address',
    'type' => 'varchar(200)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'st_contactor' => 
  array (
    'name' => 'st_contactor',
    'type' => 'varchar(20)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'st_telephone' => 
  array (
    'name' => 'st_telephone',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'st_remark' => 
  array (
    'name' => 'st_remark',
    'type' => 'varchar(200)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
);