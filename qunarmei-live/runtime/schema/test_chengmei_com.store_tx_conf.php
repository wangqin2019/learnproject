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
  'user_id' => 
  array (
    'name' => 'user_id',
    'type' => 'int(11) unsigned',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'birthday_tx_day' => 
  array (
    'name' => 'birthday_tx_day',
    'type' => 'tinyint(2) unsigned',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'birthday_tx_dt' => 
  array (
    'name' => 'birthday_tx_dt',
    'type' => 'varchar(8)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'birthday_auto_send' => 
  array (
    'name' => 'birthday_auto_send',
    'type' => 'tinyint(1) unsigned',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'holiday_tx_day' => 
  array (
    'name' => 'holiday_tx_day',
    'type' => 'tinyint(2) unsigned',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'holiday_tx_dt' => 
  array (
    'name' => 'holiday_tx_dt',
    'type' => 'varchar(8)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'holiday_auto_send' => 
  array (
    'name' => 'holiday_auto_send',
    'type' => 'tinyint(1) unsigned',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'estrangement_day' => 
  array (
    'name' => 'estrangement_day',
    'type' => 'tinyint(2) unsigned',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'is_accept_birth' => 
  array (
    'name' => 'is_accept_birth',
    'type' => 'tinyint(1) unsigned',
    'notnull' => false,
    'default' => '1',
    'primary' => false,
    'autoinc' => false,
  ),
  'is_accept_holiday' => 
  array (
    'name' => 'is_accept_holiday',
    'type' => 'tinyint(1) unsigned',
    'notnull' => false,
    'default' => '1',
    'primary' => false,
    'autoinc' => false,
  ),
  'is_accept_estrangement' => 
  array (
    'name' => 'is_accept_estrangement',
    'type' => 'tinyint(1) unsigned',
    'notnull' => false,
    'default' => '1',
    'primary' => false,
    'autoinc' => false,
  ),
  'is_accept_appoint' => 
  array (
    'name' => 'is_accept_appoint',
    'type' => 'tinyint(1) unsigned',
    'notnull' => false,
    'default' => '1',
    'primary' => false,
    'autoinc' => false,
  ),
  'create_time' => 
  array (
    'name' => 'create_time',
    'type' => 'datetime',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
);