<?php 
return array (
  'id_bank' => 
  array (
    'name' => 'id_bank',
    'type' => 'tinyint(3)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => true,
  ),
  'st_abbre_bankname' => 
  array (
    'name' => 'st_abbre_bankname',
    'type' => 'varchar(20)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'st_full_bankname' => 
  array (
    'name' => 'st_full_bankname',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'no_displayorder' => 
  array (
    'name' => 'no_displayorder',
    'type' => 'tinyint(2)',
    'notnull' => false,
    'default' => '1',
    'primary' => false,
    'autoinc' => false,
  ),
  'id_status' => 
  array (
    'name' => 'id_status',
    'type' => 'varchar(1)',
    'notnull' => false,
    'default' => '1',
    'primary' => false,
    'autoinc' => false,
  ),
  'st_bnkpic1' => 
  array (
    'name' => 'st_bnkpic1',
    'type' => 'varchar(200)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'st_bnkpic2' => 
  array (
    'name' => 'st_bnkpic2',
    'type' => 'varchar(200)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'st_bnkpic2log' => 
  array (
    'name' => 'st_bnkpic2log',
    'type' => 'varchar(200)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'st_bnkpic2txt1' => 
  array (
    'name' => 'st_bnkpic2txt1',
    'type' => 'varchar(20)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'st_bnkpic2txt2' => 
  array (
    'name' => 'st_bnkpic2txt2',
    'type' => 'varchar(20)',
    'notnull' => false,
    'default' => NULL,
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