<?php 
return array (
  'id_role' => 
  array (
    'name' => 'id_role',
    'type' => 'int(2)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => true,
  ),
  'st_role' => 
  array (
    'name' => 'st_role',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'fg_dataisolate' => 
  array (
    'name' => 'fg_dataisolate',
    'type' => 'int(1)',
    'notnull' => false,
    'default' => '1',
    'primary' => false,
    'autoinc' => false,
  ),
  'st_remark' => 
  array (
    'name' => 'st_remark',
    'type' => 'varchar(100)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
);