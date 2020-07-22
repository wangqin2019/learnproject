<?php 
return array (
  'id' => 
  array (
    'name' => 'id',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => true,
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
  'modify_time' => 
  array (
    'name' => 'modify_time',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'appid' => 
  array (
    'name' => 'appid',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'title' => 
  array (
    'name' => 'title',
    'type' => 'varchar(128)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'cover' => 
  array (
    'name' => 'cover',
    'type' => 'varchar(128)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'host_uid' => 
  array (
    'name' => 'host_uid',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
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
  'chat_room_id' => 
  array (
    'name' => 'chat_room_id',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'room_type' => 
  array (
    'name' => 'room_type',
    'type' => 'varchar(30)',
    'notnull' => false,
    'default' => 'live',
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
  'device' => 
  array (
    'name' => 'device',
    'type' => 'tinyint(4)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'admire_count' => 
  array (
    'name' => 'admire_count',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'play_url1' => 
  array (
    'name' => 'play_url1',
    'type' => 'varchar(128)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'play_url2' => 
  array (
    'name' => 'play_url2',
    'type' => 'varchar(128)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'play_url3' => 
  array (
    'name' => 'play_url3',
    'type' => 'varchar(128)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'longitude' => 
  array (
    'name' => 'longitude',
    'type' => 'double',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'latitude' => 
  array (
    'name' => 'latitude',
    'type' => 'double',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'address' => 
  array (
    'name' => 'address',
    'type' => 'varchar(128)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
);