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
  'label_content' => 
  array (
    'name' => 'label_content',
    'type' => 'varchar(100)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'cre_time' => 
  array (
    'name' => 'cre_time',
    'type' => 'datetime',
    'notnull' => false,
    'default' => '0000-00-00 00:00:00',
    'primary' => false,
    'autoinc' => false,
  ),
);