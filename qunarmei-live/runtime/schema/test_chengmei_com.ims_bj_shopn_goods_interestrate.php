<?php 
return array (
  'id_store' => 
  array (
    'name' => 'id_store',
    'type' => 'int(10)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => false,
  ),
  'id_goods' => 
  array (
    'name' => 'id_goods',
    'type' => 'int(10)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => false,
  ),
  'id_interestrate' => 
  array (
    'name' => 'id_interestrate',
    'type' => 'int(4)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
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