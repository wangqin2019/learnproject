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
  'pid' => 
  array (
    'name' => 'pid',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'pid_name' => 
  array (
    'name' => 'pid_name',
    'type' => 'varchar(30)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'fill_user_id' => 
  array (
    'name' => 'fill_user_id',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'fill_user_name' => 
  array (
    'name' => 'fill_user_name',
    'type' => 'varchar(30)',
    'notnull' => false,
    'default' => '',
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
  'score' => 
  array (
    'name' => 'score',
    'type' => 'tinyint(3)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'tips' => 
  array (
    'name' => 'tips',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'color' => 
  array (
    'name' => 'color',
    'type' => 'varchar(10)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'figure_id' => 
  array (
    'name' => 'figure_id',
    'type' => 'tinyint(2)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'form_state_id' => 
  array (
    'name' => 'form_state_id',
    'type' => 'tinyint(2)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'chest_id' => 
  array (
    'name' => 'chest_id',
    'type' => 'tinyint(2)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'abdomen_id' => 
  array (
    'name' => 'abdomen_id',
    'type' => 'tinyint(2)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'milk_id' => 
  array (
    'name' => 'milk_id',
    'type' => 'tinyint(2)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'waist_id' => 
  array (
    'name' => 'waist_id',
    'type' => 'tinyint(2)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'pelvis_id' => 
  array (
    'name' => 'pelvis_id',
    'type' => 'tinyint(2)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'bb' => 
  array (
    'name' => 'bb',
    'type' => 'varchar(4)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'right_bb' => 
  array (
    'name' => 'right_bb',
    'type' => 'varchar(4)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'left_bb' => 
  array (
    'name' => 'left_bb',
    'type' => 'varchar(4)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'bust' => 
  array (
    'name' => 'bust',
    'type' => 'varchar(4)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'lower_bust' => 
  array (
    'name' => 'lower_bust',
    'type' => 'varchar(4)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'waist' => 
  array (
    'name' => 'waist',
    'type' => 'varchar(4)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'hipline' => 
  array (
    'name' => 'hipline',
    'type' => 'varchar(4)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'thighcir' => 
  array (
    'name' => 'thighcir',
    'type' => 'varchar(4)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'left_hip_height' => 
  array (
    'name' => 'left_hip_height',
    'type' => 'varchar(4)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'right_hip_height' => 
  array (
    'name' => 'right_hip_height',
    'type' => 'varchar(4)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'lower_leg' => 
  array (
    'name' => 'lower_leg',
    'type' => 'varchar(4)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'ankle' => 
  array (
    'name' => 'ankle',
    'type' => 'varchar(4)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'bb_flag' => 
  array (
    'name' => 'bb_flag',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '2',
    'primary' => false,
    'autoinc' => false,
  ),
  'right_bb_flag' => 
  array (
    'name' => 'right_bb_flag',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '2',
    'primary' => false,
    'autoinc' => false,
  ),
  'left_bb_flag' => 
  array (
    'name' => 'left_bb_flag',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '2',
    'primary' => false,
    'autoinc' => false,
  ),
  'bust_flag' => 
  array (
    'name' => 'bust_flag',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '2',
    'primary' => false,
    'autoinc' => false,
  ),
  'lower_bust_flag' => 
  array (
    'name' => 'lower_bust_flag',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '2',
    'primary' => false,
    'autoinc' => false,
  ),
  'waist_flag' => 
  array (
    'name' => 'waist_flag',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '2',
    'primary' => false,
    'autoinc' => false,
  ),
  'hipline_flag' => 
  array (
    'name' => 'hipline_flag',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '2',
    'primary' => false,
    'autoinc' => false,
  ),
  'thighcir_flag' => 
  array (
    'name' => 'thighcir_flag',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '2',
    'primary' => false,
    'autoinc' => false,
  ),
  'left_hip_height_flag' => 
  array (
    'name' => 'left_hip_height_flag',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '2',
    'primary' => false,
    'autoinc' => false,
  ),
  'right_hip_height_flag' => 
  array (
    'name' => 'right_hip_height_flag',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '2',
    'primary' => false,
    'autoinc' => false,
  ),
  'lower_leg_flag' => 
  array (
    'name' => 'lower_leg_flag',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '2',
    'primary' => false,
    'autoinc' => false,
  ),
  'ankle_flag' => 
  array (
    'name' => 'ankle_flag',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '2',
    'primary' => false,
    'autoinc' => false,
  ),
  'yc_cnt' => 
  array (
    'name' => 'yc_cnt',
    'type' => 'tinyint(2)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'hips_id' => 
  array (
    'name' => 'hips_id',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'thigh_id' => 
  array (
    'name' => 'thigh_id',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'vertebra_id' => 
  array (
    'name' => 'vertebra_id',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'fat_id' => 
  array (
    'name' => 'fat_id',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'pain_back_id' => 
  array (
    'name' => 'pain_back_id',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '0',
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