<?php 
return array (
  'id' => 
  array (
    'name' => 'id',
    'type' => 'int(6) unsigned',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => true,
  ),
  'app_id' => 
  array (
    'name' => 'app_id',
    'type' => 'varchar(30)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'app_secret' => 
  array (
    'name' => 'app_secret',
    'type' => 'varchar(40)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'app_name' => 
  array (
    'name' => 'app_name',
    'type' => 'varchar(20)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'token_expires' => 
  array (
    'name' => 'token_expires',
    'type' => 'int(10)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'access_token' => 
  array (
    'name' => 'access_token',
    'type' => 'varchar(255)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'app_status' => 
  array (
    'name' => 'app_status',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
);