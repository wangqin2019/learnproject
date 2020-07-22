<?php 
return array (
  'id_operation' => 
  array (
    'name' => 'id_operation',
    'type' => 'bigint(10)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => true,
  ),
  'id_branch' => 
  array (
    'name' => 'id_branch',
    'type' => 'int(6)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'id_department' => 
  array (
    'name' => 'id_department',
    'type' => 'varchar(10)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'st_applicant' => 
  array (
    'name' => 'st_applicant',
    'type' => 'varchar(20)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'dt_application' => 
  array (
    'name' => 'dt_application',
    'type' => 'date',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'id_cashpurpose' => 
  array (
    'name' => 'id_cashpurpose',
    'type' => 'varchar(2)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'sm_money' => 
  array (
    'name' => 'sm_money',
    'type' => 'decimal(10,2)',
    'notnull' => false,
    'default' => '0.00',
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
  'dt_finish' => 
  array (
    'name' => 'dt_finish',
    'type' => 'datetime',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'id_operator' => 
  array (
    'name' => 'id_operator',
    'type' => 'bigint(10)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'st_remark' => 
  array (
    'name' => 'st_remark',
    'type' => 'varchar(300)',
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
  'st_col1' => 
  array (
    'name' => 'st_col1',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'st_col2' => 
  array (
    'name' => 'st_col2',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'st_col3' => 
  array (
    'name' => 'st_col3',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'st_col4' => 
  array (
    'name' => 'st_col4',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
);