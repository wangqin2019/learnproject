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
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'user_name' => 
  array (
    'name' => 'user_name',
    'type' => 'varchar(300)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'sex' => 
  array (
    'name' => 'sex',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'head_img' => 
  array (
    'name' => 'head_img',
    'type' => 'varchar(300)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'age' => 
  array (
    'name' => 'age',
    'type' => 'tinyint(2)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'height' => 
  array (
    'name' => 'height',
    'type' => 'varchar(10)',
    'notnull' => false,
    'default' => '167',
    'primary' => false,
    'autoinc' => false,
  ),
  'weight' => 
  array (
    'name' => 'weight',
    'type' => 'varchar(10)',
    'notnull' => false,
    'default' => '58',
    'primary' => false,
    'autoinc' => false,
  ),
  'occupation' => 
  array (
    'name' => 'occupation',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => '["1"]',
    'primary' => false,
    'autoinc' => false,
  ),
  'card_type' => 
  array (
    'name' => 'card_type',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => '["1"]',
    'primary' => false,
    'autoinc' => false,
  ),
  'month_income_id' => 
  array (
    'name' => 'month_income_id',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => '["1"]',
    'primary' => false,
    'autoinc' => false,
  ),
  'mobile' => 
  array (
    'name' => 'mobile',
    'type' => 'varchar(11)',
    'notnull' => false,
    'default' => '1',
    'primary' => false,
    'autoinc' => false,
  ),
  'email' => 
  array (
    'name' => 'email',
    'type' => 'varchar(30)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'weixin' => 
  array (
    'name' => 'weixin',
    'type' => 'varchar(30)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'qq' => 
  array (
    'name' => 'qq',
    'type' => 'varchar(30)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'is_return_visit' => 
  array (
    'name' => 'is_return_visit',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '1',
    'primary' => false,
    'autoinc' => false,
  ),
  'contact_time' => 
  array (
    'name' => 'contact_time',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'birthday' => 
  array (
    'name' => 'birthday',
    'type' => 'varchar(30)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'address' => 
  array (
    'name' => 'address',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'shape' => 
  array (
    'name' => 'shape',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => '["1"]',
    'primary' => false,
    'autoinc' => false,
  ),
  'over_weight' => 
  array (
    'name' => 'over_weight',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => '["1"]',
    'primary' => false,
    'autoinc' => false,
  ),
  'health' => 
  array (
    'name' => 'health',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => '["1"]',
    'primary' => false,
    'autoinc' => false,
  ),
  'eating_habits' => 
  array (
    'name' => 'eating_habits',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => '["1"]',
    'primary' => false,
    'autoinc' => false,
  ),
  'exercise' => 
  array (
    'name' => 'exercise',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => '["1"]',
    'primary' => false,
    'autoinc' => false,
  ),
  'allergy' => 
  array (
    'name' => 'allergy',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => '["1"]',
    'primary' => false,
    'autoinc' => false,
  ),
  'is_reduce_weight' => 
  array (
    'name' => 'is_reduce_weight',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => '["1"]',
    'primary' => false,
    'autoinc' => false,
  ),
  'is_vegetarian_diet' => 
  array (
    'name' => 'is_vegetarian_diet',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => '["1"]',
    'primary' => false,
    'autoinc' => false,
  ),
  'qrcode_img' => 
  array (
    'name' => 'qrcode_img',
    'type' => 'varchar(60)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'create_time' => 
  array (
    'name' => 'create_time',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
);