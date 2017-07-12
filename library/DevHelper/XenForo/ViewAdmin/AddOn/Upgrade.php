<?php

class DevHelper_XenForo_ViewAdmin_AddOn_Upgrade extends XFCP_DevHelper_XenForo_ViewAdmin_AddOn_Upgrade
{
    public function prepareParams()
    {
        if (!empty($this->_params['addOn']) && empty($this->_params['serverFile'])) {
            $addOn = $this->_params['addOn'];
            $this->_params['serverFile'] = DevHelper_Generator_File::getAddOnXmlPath($addOn);
        }

        parent::prepareParams();
    }
}

if (false) {
    class XFCP_DevHelper_XenForo_ViewAdmin_AddOn_Upgrade extends XenForo_ViewAdmin_Base
    {
    }
}
