<?php 
return array (
  'id_operationdetail' => 
  array (
    'name' => 'id_operationdetail',
    'type' => 'bigint(10)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => true,
  ),
  'id_operation' => 
  array (
    'name' => 'id_operation',
    'type' => 'bigint(10)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'id_order' => 
  array (
    'name' => 'id_order',
    'type' => 'int(10)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'sm_money' => 
  array (
    'name' => 'sm_money',
    'type' => 'decimal(10,2)',
    'notnull' => false,
    'default' => '0.00',
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