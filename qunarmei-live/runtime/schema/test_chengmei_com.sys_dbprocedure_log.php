<?php 
return array (
  'id_seq' => 
  array (
    'name' => 'id_seq',
    'type' => 'int(8)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => true,
  ),
  'st_procudure' => 
  array (
    'name' => 'st_procudure',
    'type' => 'varchar(30)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'dt_execute' => 
  array (
    'name' => 'dt_execute',
    'type' => 'date',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'dt_start' => 
  array (
    'name' => 'dt_start',
    'type' => 'datetime',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'dt_end' => 
  array (
    'name' => 'dt_end',
    'type' => 'datetime',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'fg_success' => 
  array (
    'name' => 'fg_success',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
);