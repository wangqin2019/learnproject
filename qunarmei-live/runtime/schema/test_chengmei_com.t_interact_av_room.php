<?php 
return array (
  'uid' => 
  array (
    'name' => 'uid',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => false,
  ),
  'av_room_id' => 
  array (
    'name' => 'av_room_id',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'status' => 
  array (
    'name' => 'status',
    'type' => 'varchar(20)',
    'notnull' => false,
    'default' => 'off',
    'primary' => false,
    'autoinc' => false,
  ),
  'modify_time' => 
  array (
    'name' => 'modify_time',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'role' => 
  array (
    'name' => 'role',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'video_type' => 
  array (
    'name' => 'video_type',
    'type' => 'tinyint(4)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
);