<?php 
return array (
  'id_screen' => 
  array (
    'name' => 'id_screen',
    'type' => 'varchar(6)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => false,
  ),
  'id_button' => 
  array (
    'name' => 'id_button',
    'type' => 'int(1)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => false,
  ),
  'st_button' => 
  array (
    'name' => 'st_button',
    'type' => 'varchar(20)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'id_btgroup' => 
  array (
    'name' => 'id_btgroup',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
);