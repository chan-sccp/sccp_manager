<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$def_val = array();
$dev_id = null;
$dev_new = null;
$device_warning= null;
// Default value from Server setings

$def_val['netlang'] =  array("keyword" => 'netlang', "data" => $this->sccpvalues['netlang']['data'], "seq" => "99");
$def_val['devlang'] =  array("keyword" => 'devlang', "data" => $this->sccpvalues['devlang']['data'], "seq" => "99");
$def_val['directed_pickup_context'] =  array("keyword" => 'directed_pickup_context', "data" => $this->sccpvalues['directed_pickup_context']['data'], "seq" => "99");

if (!empty($_REQUEST['new_id'])) {
    // Adding device that is connected but not in database
    $dev_id = $_REQUEST['new_id'];

    // Get device defaults from db
    $sccpDeviceDesc = $this->dbinterface->getSccpDeviceTableData('get_columns_sccpdevice');

    foreach ($sccpDeviceDesc as $data) {
        $key = (string) $data['Field'];
        $def_val[$key] = array("keyword" => $key, "data" => $data['Default'], "seq" => "99");
    }

    // Overwrite some specific defaults based on $_REQUEST
    $val = str_replace(array('SEP','ATA','VG'), '', $dev_id);
    $val = implode(':', sscanf($val, '%2s%2s%2s%2s%2s%2s')); // Convert to Cisco display Format
    $def_val['mac'] = array("keyword" => 'mac', "data" => $val, "seq" => "99");
    $val = $_REQUEST['type'];
    $def_val['type'] = array("keyword" => 'type', "data" => $val, "seq" => "99");
    if (!empty($_REQUEST['addon'])) {
        $def_val['addon'] = array("keyword" => 'type', "data" => $_REQUEST['addon'], "seq" => "99");
    }
}

//Get default values. Will use these for a new device, and modify for an existing.
$def_val = $this->getTableDefaults('sccpdevice');
if (!empty($_REQUEST['id'])) {
    // Editing an existing Device. Overwrite any defaults that are already set for this device.
    $dev_id = $_REQUEST['id'];
    $dev_new = $dev_id;
    $db_res = $this->dbinterface->getSccpDeviceTableData('get_sccpdevice_byid', array("id" => $dev_id));
    foreach ($db_res as $key => $val) {
        if (!empty($val)) {
            switch ($key) {
                case 'type':
                    $tmp_raw = $this->getSccpModelInformation('byid', true, 'all', array('model'=>$val));
                    if (!empty($tmp_raw[0])) {
                        $tmp_raw = $tmp_raw[0];
                    }
                    if (!empty($tmp_raw['validate'])) {
                        $tmpar =  explode(";", $tmp_raw['validate']);
                        if ($tmpar[0] != 'yes') {
                            $device_warning['Image'] = array('Device firmware not found : '.$tmp_raw['loadimage']);
                        }
                        if ($tmpar[1] != 'yes') {
                            $device_warning['Template'] = array('Missing device configuration template : '. $tmp_raw['nametemplate']);
                        }
                    }
                    $def_val[$key] = array("keyword" => $key, "data" => $val, "seq" => "99");
                    break;
                case 'name':
                    $key = 'mac';   //This is the key that formShow expects
                    $val = str_replace(array('SEP','ATA','VG'), '', $val);
                    $val = implode(':', sscanf($val, '%2s%2s%2s%2s%2s%2s')); // Convert to Cisco display Format
                    $def_val[$key] = array("keyword" => $key, "data" => $val, "seq" => "99");
                    break;
                case '_hwlang':
                    $tmpar =  explode(":", $val);
                    $def_val['netlang'] =  array("keyword" => 'netlang', "data" => $tmpar[0], "seq" => "99");
                    $def_val['devlang'] =  array("keyword" => 'devlang', "data" => $tmpar[1], "seq" => "99");
                    break;
//                case 'permit':
//                case 'deny':
//                    $def_val[$key . '_net'] = array("keyword" => $key, "data" => before('/', $val), "seq" => "99");
//                    $key = $key . '_mask';
//                    $val = after('/', $val);
//                    break;
                case '_phonepersonalization':
                      $def_val['phonepersonalization'] =  array("keyword" => 'phonepersonalization', "data" => $val, "seq" => "99");

                default:
                    // Overwrite existing defaults after checking that data is still valid after schema updates
                    // Do not strip underscores as these fields are new in the schema and so should be valid.
                    $enumFields = $this->getTableEnums('sccpdevice', false);
                    if (array_key_exists($key, $enumFields)){
                        // This field is (now) an enum. Check the current value is acceptable.
                        // Quote value as enum values are quoted.
                        if (in_array("'{$val}'", $enumFields[$key])) {
                            // The value is valid so will keep
                            $def_val[$key] = array("keyword" => $key, "data" => $val, "seq" => "99");
                        }
                        // Do not store value and let defaults apply
                        continue;
                    }
                    $def_val[$key] = array("keyword" => $key, "data" => $val, "seq" => "99");
                    // Need to assign defaultLine as not set in the db.
                    $def_val['defaultLine'] = $this->dbinterface->getSccpDeviceTableData('getDefaultLine', array('id' => $dev_id))['name'];
                    break;
            }
        }
    }

}

if (!empty($device_warning)) {
    ?>
    <div class="fpbx-container container-fluid">
        <div class="row">
            <div class="container">
                <h2 style="border:2px solid Tomato;color:Tomato;" >Warning in the SCCP Device</h2>
                <div class="table-responsive">
                        <?php
                        foreach ($device_warning as $key => $value) {
                            echo '<h3>'.$key.'</h3>';
                            if (is_array($value)) {
                                echo '<li>'._(implode('</li><li>', $value)).'</li>';
                            } else {
                                echo '<li>'. _($value).'</li>';
                            }
                        }
                        ?>
                    </pre>
                </div>
            </div>
        </div>
    </div>
<br>

<?php } ?>

<form autocomplete="off" name="frm_adddevice" id="frm_adddevice" class="fpbx-submit" action="" method="post" data-id="hw_edit">
    <input type="hidden" name="category" value="adddevice_form">
    <input type="hidden" name="Submit" value="Submit">

    <?php
    if (empty($dev_new)) {
        echo '<input type="hidden" name="sccp_deviceid" value="new">';
    } else {
        echo '<input type="hidden" name="sccp_deviceid" value="'.$dev_id.'">';
    }
    if (empty($dev_id)) {
        echo $this->showGroup('sccp_hw_dev', 1, 'sccp_hw', $def_val);
    } else {
        echo $this->showGroup('sccp_hw_dev_edit', 1, 'sccp_hw', $def_val);
    }
    echo $this->showGroup('sccp_hw_dev2', 1, 'sccp_hw', $def_val);
    echo $this->showGroup('sccp_hw_dev_advance', 1, 'sccp_hw', $def_val);
    echo $this->showGroup('sccp_hw_dev_softkey', 1, 'sccp_hw', $def_val);
    // echo $this->showGroup('sccp_hw_dev_pickup', 1, 'sccp_hw', $def_val); This are line properties and does not exist!
    echo $this->showGroup('sccp_hw_dev_conference', 1, 'sccp_hw', $def_val);
    echo $this->showGroup('sccp_dev_vendor_conf', 1, 'vendorconfig', $def_val);
    echo $this->showGroup('sccp_hw_dev_network', 1, 'sccp_hw', $def_val);
    ?>
</form>
