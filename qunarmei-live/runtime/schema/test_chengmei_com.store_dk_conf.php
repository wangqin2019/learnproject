<?php 
return array (
  'id' => 
  array (
    'name' => 'id',
    'type' => 'int(11) unsigned',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => true,
  ),
  'storeid' => 
  array (
    'name' => 'storeid',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'dk_begin_time' => 
  array (
    'name' => 'dk_begin_time',
    'type' => 'varchar(5)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'dk_end_time' => 
  array (
    'name' => 'dk_end_time',
    'type' => 'varchar(5)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'dk_tips' => 
  array (
    'name' => 'dk_tips',
    'type' => 'varchar(30)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'dk_month_start_time' => 
  array (
    'name' => 'dk_month_start_time',
    'type' => 'varchar(10)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
);