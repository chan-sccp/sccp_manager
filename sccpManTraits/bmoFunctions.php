<?php

namespace FreePBX\modules\Sccp_manager\sccpManTraits;

trait bmoFunctions {

    //Need to reload freePBX for modifications below to work

    public static function myConfigPageInits() {
        return array('sccpsettings', 'sccp_phone','sccp_adv');
    }

    public function doConfigPageInit($page) {
        switch ($page) {
            case 'sccpsettings':
                break;
            case 'sccp_phone':
                // Get activeDevices once and pass to functions.
                $activeDevices = $this->aminterface->sccp_get_active_device();
                $this->extensionData = json_encode($this->getExtensionGrid('extGrid', $activeDevices));
                $this->sccpPhoneData = json_encode($this->getPhoneGrid('sccp', $activeDevices));
                $this->sipPhoneData = json_encode($this->getPhoneGrid('cisco-sip'));
                break;
            case 'sccp_adv':
                $this->dialTemplateData = json_encode($this->getDialTemplate());
                $this->softKeyData = json_encode($this->getSoftKey());
                $this->deviceModelData = json_encode($this->ajaxHandler($_REQUEST = array('command'=>'getDeviceModel', 'type'=>'enabled')));
                break;
            default:
                break;
        }
    }

    function getPhoneGrid(string $type, $activeDevices =array()){

        $dbDevices = array();
        // Find all devices defined in the database.
        $dbDevices = $this->dbinterface->getSccpDeviceTableData('phoneGrid', array('type' => $type));

        // Return if only interested in SIP devices
        if ($type == 'cisco-sip') {
            return $dbDevices;     //this may be empty
        }

        foreach ($dbDevices as &$dev_id) {
            if (!empty($activeDevices[$dev_id['name']])) {
                // Device is in db and is connected
                $dev_id['description'] = $activeDevices[$dev_id['name']]['descr'];
                $dev_id['status'] = $activeDevices[$dev_id['name']]['status'];
                $dev_id['address'] = $activeDevices[$dev_id['name']]['address'];
                $dev_id['new_hw'] = 'N';
                // No further action required on this active device
                unset($activeDevices[$dev_id['name']]);
            }
        }
        unset($dev_id); // unset reference.

        if (!empty($activeDevices)) {
            // Have a device that is connected but is not currently in the database
            // This device must have connected via hotline or config in sccp.conf.
            // Pass parameters to addDevice so that can be added to db.
            foreach ($activeDevices as $dev_ids) {
                $id_name = $dev_ids['name'];
                $dev_data = $this->aminterface->sccp_getdevice_info($id_name);
                if (!empty($dev_data['SCCP_Vendor']['model_id'])) {
                    $dev_addon = $dev_data['SCCP_Vendor']['model_addon'];
                    if (empty($dev_addon)) {
                        $dev_addon = null;
                    }
                    $dev_schema = $this->getSccpModelInformation('byciscoid', false, "all", array('model' => $dev_data['SCCP_Vendor']['model_id']));
                    if (empty($dev_schema)) {
                        $dev_schema[0]['model'] = "ERROR in Model Schema";
                    }
                    $dbDevices[] = array(
                        'name' => $id_name,
                        'mac' => $id_name,
                        'button' => '---',
                        'type' => $dev_schema[0]['model'],
                        'new_hw' => 'Y',
                        'description' => '*NEW* ' . $dev_ids['descr'],
                        'status' => '*NEW* ' . $dev_ids['status'],
                        'address' => $dev_ids['address'],
                        'addon' => $dev_addon
                    );
                }
            }
        }
        return $dbDevices;
    }

    function getExtensionGrid(string $type, $activeDevices = array()) {
        $lineList = $this->dbinterface->getSccpDeviceTableData($type);
        if (empty($lineList)) {
            return array();
        }
        //$activeDevices = $this->aminterface->sccp_get_active_device();
        $uniqueLineList = array();
        foreach ($lineList as $key => &$lineArr) {
            if (array_key_exists($lineArr['mac'], $activeDevices)) {
                $lineArr['line_status'] = "{$activeDevices[$lineArr['mac']]['status']} | {$activeDevices[$lineArr['mac']]['act']}";
            }
            if (array_key_exists($lineArr['name'], $uniqueLineList)) {
                $lineList[$uniqueLineList[$lineArr['name']]]['mac'] .= '<br>' . $lineArr['mac'];
                $lineList[$uniqueLineList[$lineArr['name']]]['line_status'] .= '<br>' . $lineArr['line_status'];
                unset($lineList[$key]);  // Drop this array as no longer used
                continue;
            }
            $uniqueLineList[$lineArr['name']] = $key;
        }
        unset($lineArr, $uniqueLineList); // unset reference and temp vars.
        return array_values($lineList);   // Reindex array and return
    }

    function getDialTemplate() {
        // -------------------------------   Old device support - In the development---
        $result = array();
        $result = $this->getDialPlanList();
        return $result;
    }

    function getSoftKey() {
        $result = array();
        $i = 0;
        $keyl = 'default';
        foreach ($this->aminterface->sccp_list_keysets() as $keyl => $vall) {
            $result[$i]['softkeys'] = $keyl;
            if ($keyl == 'default') {
                foreach ($this->extconfigs->getExtConfig('keyset') as $key => $value) {
                    $result[$i][$key] = str_replace(',', '<br>', $value);
                }
            } else {
                foreach ($this->getMyConfig('softkeyset', $keyl) as $key => $value) {
                    $result[$i][$key] = str_replace(',', '<br>', $value);
                }
            }
            $i++;
        }
        return $result;
    }

    public function getMyConfig(string $var, $id = "noid") {
        $final = array();
        $i = 0;
        if ($id == "noid") {
            foreach ($this->sccp_conf_init as $key => $value) {
                if ($this->sccp_conf_init[$key]['type'] == 'softkeyset') {
                    $final[$i] = $value;
                    $i++;
                }
            }
        } else {
            if (!empty($this->sccp_conf_init[$id])) {
                if ($this->sccp_conf_init[$id]['type'] == 'softkeyset') {
                    $final = $this->sccp_conf_init[$id];
                }
            }

            $i++;
        }
        return $final;
    }
    /* unused but FPBX API requires it */

    public function install() {

    }

    /* unused but FPBX API requires it */

    public function uninstall() {

    }

    /* unused but FPBX API requires it */

    public function backup() {

    }

    /* unused but FPBX API requires it */

    public function restore($backup) {

    }

    public function getActionBar($request) {
        $buttons = array();
        switch ($request['display']) {
            case 'sccp_adv':
                if (empty($request['tech_hardware'])) {
                    break;
                }
                $buttons = array(
                    'submit' => array(
                        'name' => 'ajaxsubmit',
                        'id' => 'ajaxsubmit',
                        'value' => _("Save")
                    ),
                    'Save' => array(
                        'name' => 'ajaxsubmit2',
                        'id' => 'ajaxsubmit2',
                        'stayonpage' => 'yes',
                        'value' => _("Save + Continue")
                    ),
                    'cancel' => array(
                        'name' => 'cancel',
                        'id' => 'ajaxcancel',
                        'data-search' => '?display=sccp_adv',
                        'data-hash' => 'sccpdialplan',
                        'value' => _("Cancel")
                    ),
                );
                break;
            case 'sccp_phone':
                if (empty($request['tech_hardware'])) {
                    break;
                }
                $buttons = array(
                    'submit' => array(
                        'name' => 'ajaxsubmit',
                        'id' => 'ajaxsubmit',
                        'value' => _("Save")
                    ),
                    'Save' => array(
                        'name' => 'ajaxsubmit2',
                        'id' => 'ajaxsubmit2',
                        'stayonpage' => 'yes',
                        'value' => _("Save + Continue")
                    ),
                    'cancel' => array(
                        'name' => 'cancel',
                        'id' => 'ajaxcancel',
                        'data-search' => '?display=sccp_phone',
                        'data-hash' => 'sccpdevice',
                        'value' => _("Cancel")
                    ),
                );
                break;
            case 'sccpsettings':
                // TODO: Need to change to have save and save and continue
                $buttons = array(
                    'submit' => array(
                        'name' => 'ajaxsubmit',
                        'id' => 'ajaxsubmit',
                        'value' => _("Save")
                    ),
                    'reset' => array(
                        'name' => 'reset',
                        'id' => 'ajaxcancel',
                        'data-reload' => 'reload',
                        'value' => _("Cancel")
                    ),
                );
                break;
        }
        return $buttons;
    }

    public function getRightNav($request) {
        global $amp_conf;
        if (isset($request['tech_hardware']) && ($request['tech_hardware'] == 'cisco')) {
            return load_view($amp_conf['AMPWEBROOT'] .'/admin/modules/sccp_manager/views/hardware.rnav.php', array('data' => $this->sccpPhoneData));
        }
    }

    public function doGeneralPost() {
        if (!isset($_REQUEST['Submit'])) {
            return;
        }
    }
}
?>
