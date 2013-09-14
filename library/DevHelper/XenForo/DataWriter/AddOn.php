<?php

class DevHelper_XenForo_DataWriter_AddOn extends XFCP_DevHelper_XenForo_DataWriter_AddOn
{
	protected function _preSave()
	{
		if (isset($GLOBALS[DevHelper_Listener::XENFORO_CONTROLLERADMIN_ADDON_SAVE]))
		{
			$GLOBALS[DevHelper_Listener::XENFORO_CONTROLLERADMIN_ADDON_SAVE]->DevHelper_actionSave($this);
		}
		
		return parent::_preSave();
	}
}
