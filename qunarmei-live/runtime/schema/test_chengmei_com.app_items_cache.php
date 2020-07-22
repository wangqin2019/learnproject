<?php 
return array (
  'id_itemcache' => 
  array (
    'name' => 'id_itemcache',
    'type' => 'varchar(10)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => false,
  ),
  'id_itemtype' => 
  array (
    'name' => 'id_itemtype',
    'type' => 'varchar(6)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => false,
  ),
  'id_restype' => 
  array (
    'name' => 'id_restype',
    'type' => 'varchar(4)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'st_respath' => 
  array (
    'name' => 'st_respath',
    'type' => 'varchar(200)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'dt_insert' => 
  array (
    'name' => 'dt_insert',
    'type' => 'datetime',
    'notnull' => false,
    'default' => 'CURRENT_TIMESTAMP',
    'primary' => false,
    'autoinc' => false,
  ),
  'dt_update' => 
  array (
    'name' => 'dt_update',
    'type' => 'datetime',
    'notnull' => false,
    'default' => 'CURRENT_TIMESTAMP',
    'primary' => false,
    'autoinc' => false,
  ),
);