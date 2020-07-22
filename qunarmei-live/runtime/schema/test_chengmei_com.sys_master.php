<?php 
return array (
  'st_masterkey' => 
  array (
    'name' => 'st_masterkey',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => false,
  ),
  'id_master' => 
  array (
    'name' => 'id_master',
    'type' => 'varchar(2)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => false,
  ),
  'st_mastervalue' => 
  array (
    'name' => 'st_mastervalue',
    'type' => 'varchar(100)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
);