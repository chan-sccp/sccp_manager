<?php

namespace FreePBX\modules\Sccp_manager\sccpManTraits;

trait ajaxHelper {

    public function ajaxRequest($req, &$setting) {
        // Called first by BMO. Must return true or request will be aborted.
        // See https://wiki.freepbx.org/display/FOP/BMO+Ajax+Calls
        switch ($req) {
            case 'backupsettings':
            case 'savesettings':
            case 'save_device':
            case 'save_sip_device':
            case 'save_ruser':
            case 'save_dialplan_template':
            case 'delete_hardware':
            case 'getPhoneGrid':
            case 'getExtensionGrid':
            case 'getDeviceModel':
            case 'getUserGrid':
            case 'getSoftKey':
            case 'getDialTemplate':
            case 'create_hw_tftp':
            case 'reset_dev':
            case 'reset_token':
            case 'model_enabled':
            case 'model_disabled':
            case 'model_update':
            case 'model_add':
            case 'model_delete':
            case 'update_button_label':
            case 'updateSoftKey':
            case 'deleteSoftKey':
            case 'delete_dialplan':
                return true;
                break;
            default:
                return false;
        }
    }

    // ajaxHandler is called after ajaxRequest returns true
    public function ajaxHandler() {
        $request = $_REQUEST;
        $msg = array();
        $cmd_id = $request['command'];
        switch ($cmd_id) {
            case 'savesettings':
                $action = isset($request['sccp_createlangdir']) ? $request['sccp_createlangdir'] : '';
                if ($action == 'yes') {
                    $this->initializeTFtpLanguagePath();
                }
                $this->handleSubmit($request);
                // $this->saveSccpSettings();
                //$this->createDefaultSccpConfig();
                $this->createDefaultSccpXml();

                $res = $this->aminterface->core_sccp_reload();
                $msg [] = 'Config Saved: ' . $res['Response'];
                $msg [] = 'Info :' . $res['data'];
                // !TODO!: It is necessary in the future to check, and replace all server responses on correct messages. Use _(msg)
                return array('status' => true, 'message' => $msg, 'reload' => true);
                break;
            case 'save_sip_device':
            case 'save_device':
                $this->saveSccpDevice($request);
                return array('status' => true, 'search' => '?display=sccp_phone', 'hash' => 'sccpdevice');

                break;
            case 'save_ruser':
                //$res = $request;
                $res = $this->handleRoamingUsers($request);
                return array('status' => true, 'search' => '?display=sccp_phone', 'hash' => 'general');
                break;
            case 'save_dialplan_template':
                /* !TODO!: -TODO-: dialplan templates should be removed (only required for very old devices (like ATA) */
                // -------------------------------   Old +  Sip device support - In the development---
                $res = $this->saveDialPlan($request);
                //public
                if (empty($res)) {
                    return array('status' => true, 'search' => '?display=sccp_adv', 'hash' => 'sccpdialplan');
                } else {
                    return array('status' => false, 'message' => print_r($res));
                }
                break;
            case 'delete_dialplan':
                if (!empty($request['dialplan'])) {
                    $get_file = $request['dialplan'];
                    $res = $this->deleteDialPlan($get_file);
                    return array('status' => true, 'message' => 'Dial Template has been deleted ! ', 'table_reload' => true);
                } else {
                    return array('status' => false, 'message' => print_r($res));
                }
                break;
            // -------------------------------   Old device support - In the development---
            case 'delete_hardware':
                if (!empty($request['idn'])) {
                    foreach ($request['idn'] as $idv) {
                        if ($this->strpos_array($idv, array('SEP', 'ATA', 'VG')) !== false) {
                            $this->dbinterface->write('sccpdevice', array('name' => $idv), 'delete', "name");
                            $this->dbinterface->write('sccpbuttons', array(), 'delete', '', $idv);
                            $this->deleteSccpDeviceXML($idv); // Концы в вводу !!
                            $this->aminterface->sccpDeviceReset($idv, 'reset');
                        }
                    }
                    return array('status' => true, 'table_reload' => true, 'message' => 'Hardware device has been deleted! ');
                }
                break;
            case 'create_hw_tftp':
                $ver_id = ' Test !';
                if (!empty($request['idn'])) {
                    $models = array();
                    foreach ($request['idn'] as $idv) {
                        $this->deleteSccpDeviceXML($idv);
                        $models [] = array('name' => $idv);
                    }
                } else {
                    $this->deleteSccpDeviceXML('all');
                    $models = $this->dbinterface->HWextension_db_SccpTableData("SccpDevice");
                }

                $this->createDefaultSccpXml(); // Default XML
                $ver_id = ' on found active model !';
                foreach ($models as $data) {
                    $ver_id = $this->createSccpDeviceXML($data['name']);
                    if ($ver_id == -1) {
                        return array('status' => false, 'message' => 'Error Create Configuration Divice :' . $data['name']);
                    }
                };

                if ($this->sccpvalues['siptftp']['data'] == 'on') { // Check SIP Support Enabled
                    $this->createSccpXmlSoftkey(); // Create Softkey Sets for SIP
                }
                // !TODO!: -TODO-: Do these returned message strings work with i18n ?
                return array('status' => true, 'message' => 'Create new config files (version:' . $ver_id . ')');

                break;
            case 'reset_token':
            case 'reset_dev':
                $msg = '';
                $msgr = array();
                $msgr[] = "Reset command sent to device(s) ";
                if (!empty($request['name'])) {
                    foreach ($request['name'] as $idv) {
                        $msg = strpos($idv, 'SEP-');
                        if (!(strpos($idv, 'SEP') === false)) {
                            if ($cmd_id == 'reset_token') {
                                $res = $this->aminterface->sccpDeviceReset($idv, 'tokenack');
                                $msgr[] = $msg . ' ' . $res['Response'] . ' ' . $res['data'];
                            } else {
                                $res = $this->aminterface->sccpDeviceReset($idv, 'reset');
                                $msgr[] = $msg . ' ' . $res['Response'] . ' ' . $res['data'];
                            }
                        }

                        if ($idv == 'all') {
                            $dev_list = $this->aminterface->sccp_get_active_device();
                            foreach ($dev_list as $key => $data) {
                                if ($cmd_id == 'reset_token') {
                                    if (($data['token'] == 'Rej') || ($data['status'] == 'Token ')) {
                                        $res = $this->aminterface->sccpDeviceReset($idv, 'tokenack');
                                        $msgr[] = 'Sent Token reset to :' . $key;
                                    }
                                } else {
                                    $res = $this->aminterface->sccpDeviceReset($idv, 'reset');
                                    $msgr[] = $res['Response'] . ' ' . $res['data'];
                                }
                            }
                        }
                    }
                }
                return array('status' => (($res['Response'] == 'Error')? false : true ), 'message' => $msgr, 'reload' => false, 'table_reload' => true);
                break;
            case 'update_button_label':
                $msg = '';
                $hw_list = array();
                if (!empty($request['name'])) {
                    foreach ($request['name'] as $idv) {
                        if (!(strpos($idv, 'SEP') === false)) {
                            $hw_list[] = array('name' => $idv);
                        }
                        if ($idv == 'all') {

                        }
                    }
                }
                $res = $this->updateSccpButtons($hw_list);
                $msg .= $res['Response'] . (empty($res['data']) ? '' : ' raw data: ' . $res['data'] . ' ');
                return array('status' => true, 'message' => 'Update Buttons Labels Complete: ' . $msg, 'reload' => false, 'table_reload' => true);
            case 'model_add':
                $save_settings = array();
                $key_name = array('model', 'vendor', 'dns', 'buttons', 'loadimage', 'loadinformationid', 'nametemplate');
                $upd_mode = 'replace';
            case 'model_update':
                if ($request['command'] == 'model_update') {
                    $key_name = array('model','vendor','dns', 'buttons', 'loadimage', 'loadinformationid', 'nametemplate');
                    $upd_mode = 'update';
                }
                if (!empty($request['model'])) {
                    foreach ($key_name as $key => $value) {
                        if (!empty($request[$value])) {
                            $save_settings[$value] = $request[$value];
                        } else {
                            $save_settings[$value] = $this->val_null; // null
                        }
                    }
                    $this->dbinterface->write('sccpdevmodel', $save_settings, $upd_mode, "model");
                    return array('status' => true, 'table_reload' => true);
                }
                return $save_settings;
                break;
            case 'model_enabled':
                $model_set = '1';     // fall through intentionally
            case 'model_disabled':
                if ($request['command'] == 'model_disabled') {
                    $model_set = '0';
                }
                $msg = '';
                $save_settings = array();
                if (!empty($request['model'])) {
                    foreach ($request['model'] as $idv) {
                        $this->dbinterface->write('sccpdevmodel', array('model' => $idv, 'enabled' => $model_set), 'update', "model");
                    }
                }
                return array('status' => true, 'table_reload' => true);
                break;
            case 'model_delete':
                if (!empty($request['model'])) {
                    $this->dbinterface->write('sccpdevmodel', array('model' => $request['model']), 'delete', "model");
                    return array('status' => true, 'table_reload' => true);
                }
                break;
            case 'getDeviceModel':
                switch ($request['type']) {
                    case 'all':
                    case 'extension':
                    case 'enabled':
                        $devices = $this->getSccpModelInformation($request['type'], $validate = true);
                        break;
                }
                if (empty($devices)) {
                    return array();
                }
                return $devices;
                break;

            case 'deleteSoftKey':
                if (!empty($request['softkey'])) {
                    $id_name = $request['softkey'];
                    unset($this->sccp_conf_init[$id_name]);
                    $this->createDefaultSccpConfig();
                    $msg = print_r($this->aminterface->core_sccp_reload(), 1);
                    return array('status' => true, 'table_reload' => true);
                }
                break;
            case 'updateSoftKey':
                if (!empty($request['id'])) {
                    $id_name = preg_replace('/[^A-Za-z0-9]/', '', $request['id']);
                    $this->sccp_conf_init[$id_name]['type'] = "softkeyset";
                    foreach ($this->extconfigs->getextConfig('keyset') as $keyl => $vall) {
                        if (!empty($request[$keyl])) {
                            $this->sccp_conf_init[$id_name][$keyl] = $request[$keyl];
                        }
                    }
                    $this->createDefaultSccpConfig();

                    // !TODO!: -TODO-:  Check SIP Support Enabled
                    $this->createSccpXmlSoftkey();
                    $msg = print_r($this->aminterface->core_sccp_reload, 1);
                    return array('status' => true, 'table_reload' => true);
                }
                break;
            case 'getSoftKey':
                $result = array();
                $i = 0;
                $keyl = 'default';
                foreach ($this->aminterface->sccp_list_keysets() as $keyl => $vall) {
                    $result[$i]['softkeys'] = $keyl;
                    if ($keyl == 'default') {
                        foreach ($this->extconfigs->getextConfig('keyset') as $key => $value) {
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
                break;
            case 'getExtensionGrid':
                $result = $this->dbinterface->HWextension_db_SccpTableData('SccpExtension');
                if (empty($result)) {
                    return array();
                }
                return $result;
                break;
            case 'getPhoneGrid':
                $cmd_type = !empty($request['type']) ? $request['type'] : '';

                $result = $this->dbinterface->HWextension_db_SccpTableData('SccpDevice', array('type' => $cmd_type));
                if ($cmd_type == 'cisco-sip') {
                    return $result;
                }
                $staus = $this->aminterface->sccp_get_active_device();
                if (empty($result)) {
                    $result = array();
                } else {
                    foreach ($result as &$dev_id) {
                        $id_name = $dev_id['name'];
                        if (!empty($staus[$id_name])) {
                            $dev_id['description'] = $staus[$id_name]['descr'];
                            $dev_id['status'] = $staus[$id_name]['status'];
                            $dev_id['address'] = $staus[$id_name]['address'];
                            $dev_id['new_hw'] = 'N';
                            $staus[$id_name]['news'] = 'N';
                        } else {
                            $dev_id['description'] = '- -';
                            $dev_id['status'] = 'not connected';
                            $dev_id['address'] = '- -';
                        }
                    }
                }
                if (!empty($staus)) {
                    foreach ($staus as $dev_ids) {
                        $id_name = $dev_ids['name'];
                        if (empty($dev_ids['news'])) {
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
                                $result[] = array(
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
                }
                return $result;
                break;
            case 'getDialTemplate':
                // -------------------------------   Old device support - In the development---
                $result = $this->getDialPlanList();
                if (empty($result)) {
                    $result = array();
                }
                return $result;
                break;
            case 'backupsettings':
                // -------------------------------   Old device support - In the development---
                $filename = $this->createSccpBackup();
                $file_name = basename($filename);

                header("Content-Type: application/zip");
                header("Content-Disposition: attachment; filename=$file_name");
                header("Content-Length: " . filesize($filename));

                readfile($filename);
                unlink($filename);

                // return array('status' => false, 'message' => $result);
                return $result;
                break;
        }
    }
}

?>
