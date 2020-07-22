<?php 
return array (
  'id_catecruselpic' => 
  array (
    'name' => 'id_catecruselpic',
    'type' => 'int(10)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => true,
  ),
  'id_category' => 
  array (
    'name' => 'id_category',
    'type' => 'int(10)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'st_picpath' => 
  array (
    'name' => 'st_picpath',
    'type' => 'varchar(250)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
);