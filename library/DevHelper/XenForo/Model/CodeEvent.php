<?php

class DevHelper_XenForo_Model_CodeEvent extends XFCP_DevHelper_XenForo_Model_CodeEvent
{
    public function getEventListenersByAddOn($addOnId)
    {
        return $this->fetchAllKeyed('
			SELECT *
			FROM xf_code_event_listener
			WHERE addon_id = ?
			ORDER BY event_id, callback_class, callback_method, hint
		', 'event_listener_id', $addOnId);
    }
}
