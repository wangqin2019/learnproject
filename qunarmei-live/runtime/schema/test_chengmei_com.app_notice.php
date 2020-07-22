<?php 
return array (
  'id_notice' => 
  array (
    'name' => 'id_notice',
    'type' => 'varchar(7)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => false,
  ),
  'id_page' => 
  array (
    'name' => 'id_page',
    'type' => 'varchar(6)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'id_itemtype' => 
  array (
    'name' => 'id_itemtype',
    'type' => 'varchar(6)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'id_dispaytype' => 
  array (
    'name' => 'id_dispaytype',
    'type' => 'varchar(6)',
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
  'st_content' => 
  array (
    'name' => 'st_content',
    'type' => 'varchar(200)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'fg_sysuse' => 
  array (
    'name' => 'fg_sysuse',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '1',
    'primary' => false,
    'autoinc' => false,
  ),
  'st_bartitle' => 
  array (
    'name' => 'st_bartitle',
    'type' => 'varchar(20)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'st_bar_respath' => 
  array (
    'name' => 'st_bar_respath',
    'type' => 'varchar(200)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'dt_start' => 
  array (
    'name' => 'dt_start',
    'type' => 'datetime',
    'notnull' => false,
    'default' => 'CURRENT_TIMESTAMP',
    'primary' => false,
    'autoinc' => false,
  ),
  'dt_end' => 
  array (
    'name' => 'dt_end',
    'type' => 'datetime',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
);