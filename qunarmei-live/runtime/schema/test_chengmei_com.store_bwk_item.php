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
    'type' => 'int(10)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'item_name' => 
  array (
    'name' => 'item_name',
    'type' => 'varchar(20)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'item_img' => 
  array (
    'name' => 'item_img',
    'type' => 'varchar(100)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'is_delete' => 
  array (
    'name' => 'is_delete',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'status' => 
  array (
    'name' => 'status',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'color' => 
  array (
    'name' => 'color',
    'type' => 'varchar(10)',
    'notnull' => false,
    'default' => '#5BC96D',
    'primary' => false,
    'autoinc' => false,
  ),
  'duration' => 
  array (
    'name' => 'duration',
    'type' => 'smallint(3) unsigned',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'item_price' => 
  array (
    'name' => 'item_price',
    'type' => 'decimal(5,2) unsigned',
    'notnull' => false,
    'default' => '0.00',
    'primary' => false,
    'autoinc' => false,
  ),
  'line_price' => 
  array (
    'name' => 'line_price',
    'type' => 'decimal(5,2) unsigned',
    'notnull' => false,
    'default' => '0.00',
    'primary' => false,
    'autoinc' => false,
  ),
  'experience_price' => 
  array (
    'name' => 'experience_price',
    'type' => 'decimal(5,2) unsigned',
    'notnull' => false,
    'default' => '0.00',
    'primary' => false,
    'autoinc' => false,
  ),
  'experience_price_flag' => 
  array (
    'name' => 'experience_price_flag',
    'type' => 'tinyint(1) unsigned',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'item_detail' => 
  array (
    'name' => 'item_detail',
    'type' => 'varchar(5000)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'item_wheplan_img' => 
  array (
    'name' => 'item_wheplan_img',
    'type' => 'varchar(300)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'item_detail_img' => 
  array (
    'name' => 'item_detail_img',
    'type' => 'varchar(300)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'label_id' => 
  array (
    'name' => 'label_id',
    'type' => 'tinyint(2) unsigned',
    'notnull' => false,
    'default' => '1',
    'primary' => false,
    'autoinc' => false,
  ),
  'id_interestrate' => 
  array (
    'name' => 'id_interestrate',
    'type' => 'varchar(30)',
    'notnull' => false,
    'default' => '6,8',
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
  'audit_id' => 
  array (
    'name' => 'audit_id',
    'type' => 'int(11) unsigned',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'update_time' => 
  array (
    'name' => 'update_time',
    'type' => 'timestamp',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
);