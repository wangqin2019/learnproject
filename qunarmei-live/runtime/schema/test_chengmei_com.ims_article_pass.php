<?php 
return array (
  'weid' => 
  array (
    'name' => 'weid',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => false,
  ),
  'openids' => 
  array (
    'name' => 'openids',
    'type' => 'varchar(1000)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'nickname' => 
  array (
    'name' => 'nickname',
    'type' => 'varchar(100)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'updatetime' => 
  array (
    'name' => 'updatetime',
    'type' => 'datetime',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'createtime' => 
  array (
    'name' => 'createtime',
    'type' => 'datetime',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
);