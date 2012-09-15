<?php

$CREATE_TYPES = array(
	'code_event_listener',
);

$CREATE_CODE_EVENTS = array(
	'container_admin_params',
	'container_public_params',
	'controller_pre_dispatch',
	'criteria_page',
	'criteria_user',
	'file_health_check',
	'front_controller_post_view',
	'front_controller_pre_dispatch',
	'front_controller_pre_route',
	'front_controller_pre_view',
	'init_dependencies',
	'load_class_bb_code',
	'load_class_controller',
	'load_class_datawriter',
	'load_class_importer',
	'load_class_mail',
	'load_class_model',
	'load_class_route_prefix',
	'load_class_search_data',
	'load_class_view',
	'navigation_tabs',
	'option_captcha_render',
	'search_source_create',
	'template_create',
	'template_hook',
	'template_post_render',
	'visitor_setup',
);

if (empty($IS_COMPLETION))
{
	
}
else
{
	if (count($PARAMS) == 0)
	{
		$candidates += $CREATE_TYPES;
	}
	elseif (count($PARAMS) == 1 AND !in_array($PARAMS[0], $CREATE_TYPES))
	{
		$candidates += $CREATE_TYPES;
	}
	else
	{
		switch ($PARAMS[0])
		{
			case 'code_event_listener':
				if (count($PARAMS) == 1)
				{
					$candidates += $CREATE_CODE_EVENTS;
				}
				elseif (count($PARAMS) == 2 AND !in_array($PARAMS[1], $CREATE_CODE_EVENTS))
				{
					$candidates += $CREATE_CODE_EVENTS;
				}
				else
				{
					$candidates += Helper_Common::getAddOnIds();
				}
				break;
		}
	}
}