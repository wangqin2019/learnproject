<?php 
return array (
  'id_menu' => 
  array (
    'name' => 'id_menu',
    'type' => 'varchar(4)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => false,
  ),
  'st_menu' => 
  array (
    'name' => 'st_menu',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'id_parent' => 
  array (
    'name' => 'id_parent',
    'type' => 'varchar(4)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'fg_display' => 
  array (
    'name' => 'fg_display',
    'type' => 'int(1)',
    'notnull' => false,
    'default' => '1',
    'primary' => false,
    'autoinc' => false,
  ),
  'id_order' => 
  array (
    'name' => 'id_order',
    'type' => 'int(2)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
);