<?php 
return array (
  'summary_id' => 
  array (
    'name' => 'summary_id',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => NULL,
    'primary' => true,
    'autoinc' => true,
  ),
  'summary_deal' => 
  array (
    'name' => 'summary_deal',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'summary_deal_today' => 
  array (
    'name' => 'summary_deal_today',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'summary_member' => 
  array (
    'name' => 'summary_member',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'summary_memner_today' => 
  array (
    'name' => 'summary_memner_today',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'summary_merchant' => 
  array (
    'name' => 'summary_merchant',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'summary_merchant_nobind' => 
  array (
    'name' => 'summary_merchant_nobind',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'summary_focus' => 
  array (
    'name' => 'summary_focus',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'summary_focus_today' => 
  array (
    'name' => 'summary_focus_today',
    'type' => 'int(11)',
    'notnull' => false,
    'default' => '0',
    'primary' => false,
    'autoinc' => false,
  ),
  'summary_time' => 
  array (
    'name' => 'summary_time',
    'type' => 'timestamp',
    'notnull' => false,
    'default' => 'CURRENT_TIMESTAMP',
    'primary' => false,
    'autoinc' => false,
  ),
);