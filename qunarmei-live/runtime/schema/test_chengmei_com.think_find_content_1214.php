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
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'article_img' => 
  array (
    'name' => 'article_img',
    'type' => 'varchar(3000)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'article_title' => 
  array (
    'name' => 'article_title',
    'type' => 'varchar(100)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'article_content' => 
  array (
    'name' => 'article_content',
    'type' => 'varchar(10000)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'article_video' => 
  array (
    'name' => 'article_video',
    'type' => 'varchar(300)',
    'notnull' => false,
    'default' => NULL,
    'primary' => false,
    'autoinc' => false,
  ),
  'comment_time' => 
  array (
    'name' => 'comment_time',
    'type' => 'datetime',
    'notnull' => false,
    'default' => '0000-00-00 00:00:00',
    'primary' => false,
    'autoinc' => false,
  ),
  'display_order' => 
  array (
    'name' => 'display_order',
    'type' => 'int(10)',
    'notnull' => false,
    'default' => '1',
    'primary' => false,
    'autoinc' => false,
  ),
  'article_label' => 
  array (
    'name' => 'article_label',
    'type' => 'varchar(100)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'article_label_color' => 
  array (
    'name' => 'article_label_color',
    'type' => 'varchar(100)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'resource' => 
  array (
    'name' => 'resource',
    'type' => 'int(1)',
    'notnull' => false,
    'default' => '1',
    'primary' => false,
    'autoinc' => false,
  ),
  'isshow' => 
  array (
    'name' => 'isshow',
    'type' => 'int(1)',
    'notnull' => false,
    'default' => '1',
    'primary' => false,
    'autoinc' => false,
  ),
  'summary' => 
  array (
    'name' => 'summary',
    'type' => 'varchar(100)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'is_fashion' => 
  array (
    'name' => 'is_fashion',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'cover_img' => 
  array (
    'name' => 'cover_img',
    'type' => 'varchar(100)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'cover_img_1' => 
  array (
    'name' => 'cover_img_1',
    'type' => 'varchar(100)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'cate_id' => 
  array (
    'name' => 'cate_id',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'flag_img' => 
  array (
    'name' => 'flag_img',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'article_jump_url' => 
  array (
    'name' => 'article_jump_url',
    'type' => 'varchar(50)',
    'notnull' => false,
    'default' => '',
    'primary' => false,
    'autoinc' => false,
  ),
  'type' => 
  array (
    'name' => 'type',
    'type' => 'tinyint(1)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
);