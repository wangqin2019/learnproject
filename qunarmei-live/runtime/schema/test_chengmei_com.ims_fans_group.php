<?php 
return array (
  'id' => 
  array (
    'name' => 'id',
    'type' => 'int(3)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => true,
  ),
  'groupname' => 
  array (
    'name' => 'groupname',
    'type' => 'varchar(100)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'credit' => 
  array (
    'name' => 'credit',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'info' => 
  array (
    'name' => 'info',
    'type' => 'varchar(250)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'weid' => 
  array (
    'name' => 'weid',
    'type' => 'tinyint(5)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
);