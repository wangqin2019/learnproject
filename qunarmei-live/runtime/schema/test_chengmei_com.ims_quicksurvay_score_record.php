<?php 
return array (
  'record_id' => 
  array (
    'name' => 'record_id',
    'type' => 'int(10) unsigned',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => true,
  ),
  'from_user' => 
  array (
    'name' => 'from_user',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'paper_id' => 
  array (
    'name' => 'paper_id',
    'type' => 'int(10) unsigned',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'paper_title' => 
  array (
    'name' => 'paper_title',
    'type' => 'varchar(1024)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'choice_ids' => 
  array (
    'name' => 'choice_ids',
    'type' => 'varchar(1024)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'user_choices' => 
  array (
    'name' => 'user_choices',
    'type' => 'varchar(1024)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'usermark' => 
  array (
    'name' => 'usermark',
    'type' => 'int(10) unsigned',
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
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'weid' => 
  array (
    'name' => 'weid',
    'type' => 'int(10) unsigned',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
);