<?php 
return array (
  'id_interestrate' => 
  array (
    'name' => 'id_interestrate',
    'type' => 'int(4)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => true,
  ),
  'id_bank' => 
  array (
    'name' => 'id_bank',
    'type' => 'tinyint(3)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'no_period' => 
  array (
    'name' => 'no_period',
    'type' => 'tinyint(2)',
    'notnull' => false,
    'default' => '12',
    'primary' => false,
    'autoinc' => false,
  ),
  'dt_irate_startdate' => 
  array (
    'name' => 'dt_irate_startdate',
    'type' => 'date',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'dt_irate_enddate' => 
  array (
    'name' => 'dt_irate_enddate',
    'type' => 'date',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'sm_interestrate' => 
  array (
    'name' => 'sm_interestrate',
    'type' => 'decimal(6,5)',
    'notnull' => false,
    'default' => '0.00000',
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