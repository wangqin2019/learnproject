<?php 
return array (
  'weid' => 
  array (
    'name' => 'weid',
    'type' => 'int(10) unsigned',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => false,
  ),
  'module' => 
  array (
    'name' => 'module',
    'type' => 'varchar(100)',
    'notnull' => false,
    'default' => '',
    'primary' => true,
    'autoinc' => false,
  ),
  'type' => 
  array (
    'name' => 'type',
    'type' => 'tinyint(4)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'credits' => 
  array (
    'name' => 'credits',
    'type' => 'varchar(5000)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'createtime' => 
  array (
    'name' => 'createtime',
    'type' => 'int(10) unsigned',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
);