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
  'store_id' => 
  array (
    'name' => 'store_id',
    'type' => 'int(11) unsigned',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'month_total_sales' => 
  array (
    'name' => 'month_total_sales',
    'type' => 'varchar(15)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'month_total_customer' => 
  array (
    'name' => 'month_total_customer',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'month_two_no_arrive' => 
  array (
    'name' => 'month_two_no_arrive',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'month_six_no_arrive' => 
  array (
    'name' => 'month_six_no_arrive',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'month_twelve_no_arrive' => 
  array (
    'name' => 'month_twelve_no_arrive',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'create_at' => 
  array (
    'name' => 'create_at',
    'type' => 'char(7)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
);