<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$def_val = array();
$dev_id = null;
$device_warning= null;
// Default value from Server setings
//Get default values. Will use these for a new device, and modify for an existing.
$def_val = $this->getTableDefaults('sccpdevice');
$def_val['netlang'] =  array("keyword" => 'netlang', "data" => $this->sccpvalues['netlang']['data'], "seq" => "99");
$def_val['devlang'] =  array("keyword" => 'devlang', "data" => $this->sccpvalues['devlang']['data'], "seq" => "99");
$def_val['directed_pickup_context'] =  array("keyword" => 'directed_pickup_context', "data" => $this->sccpvalues['directed_pickup_context']['data'], "seq" => "99");

if (!empty($_REQUEST['new_id'])) {
    // Adding device that is connected but not in database
    $dev_id = $_REQUEST['new_id'];
    // Overwrite some specific defaults based on $_REQUEST
    $def_val['type'] = array("keyword" => 'type', "data" => $_REQUEST['type'], "seq" => "99");
    if (!empty($_REQUEST['addon'])) {
        $def_val['addon'] = array("keyword" => 'type', "data" => $_REQUEST['addon'], "seq" => "99");
    }
}

if (!empty($_REQUEST['id'])) {
    // Editing an existing Device. Overwrite any defaults that are already set for this device.
    $dev_id = $_REQUEST['id'];

    $db_res = $this->dbinterface->getSccpDeviceTableData('get_sccpdevice_byid', array("id" => $dev_id));
    $enumFields = $this->getTableEnums('sccpdevice');
    $def_val['defaultLine'] = $this->dbinterface->getSccpDeviceTableData('getDefaultLine', array('id' => $dev_id))['name'];
    foreach ($db_res as $key => $val) {
        if (empty($val)) {
            continue;
        }
        switch ($key) {
            case 'phonepersonalization':
                $def_val['phonepersonalization'] =  array("keyword" => 'phonepersonalization', "data" => $val, "seq" => "99");
                break;
            default:
                // Overwrite existing defaults after checking that data is still valid after schema updates
                if (array_key_exists($key, $enumFields)){
                    // This field is (now) an enum. Check the current value is acceptable.
                    // Quote value as enum values are quoted.
                    if (in_array("'${val}'", $enumFields[$key])) {
                        // The value is valid so will keep
                        $def_val[$key] = array('keyword' => $key, 'data' => $val, 'seq' => 99);
                    }
                    // Do not store (invalid) value and let defaults apply
                    break;
                }
                $def_val[$key] = array("keyword" => $key, "data" => $val, "seq" => "99");
                break;
        }
    }
}
if (empty($dev_id)) {
    $dev_id = 'new';
} else {
    $val = str_replace(array('SEP','ATA','VG'), '', $dev_id);
    $val = implode(':', sscanf($val, '%2s%2s%2s%2s%2s%2s')); // Convert to Cisco display Format
    $def_val['mac'] = array("keyword" => 'mac', "data" => $val, "seq" => "99");
}

if (!empty($def_val['type']['data'])) {
    $tmp_raw = $this->getSccpModelInformation('byid', true, 'all', array('model'=>$def_val['type']['data']));
    if (isset($tmp_raw[$def_val['type']['data']])) {
        $tmp_raw = $tmp_raw[$def_val['type']['data']];
    }
    if (!$tmp_raw['fwFound']) {
        $device_warning['Image'] = array('Device firmware not found : '.$tmp_raw['loadimage']);
    }
    if (!$tmp_raw['templateFound']) {
        $device_warning['Template'] = array('Missing device configuration template : '. $tmp_raw['nametemplate']);
    }
    if (!empty($device_warning)) {
        ?>
        <div class="fpbx-container container-fluid">
            <div class="row">
                <div class="container">
                    <h2 style="border:2px solid Tomato;color:Tomato;" >Warning in the SCCP Device</h2>
                    <div class="table-responsive">
                        <pre>
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
<?php   }
} ?>

<form autocomplete="off" name="frm_adddevice" id="frm_adddevice" class="fpbx-submit" action="" method="post" data-id="hw_edit">
    <input type="hidden" name="category" value="adddevice_form">
    <input type="hidden" name="Submit" value="Submit">

    <?php

    echo '<input type="hidden" name="sccp_device_id" value="'.$dev_id.'">';

    if ($_REQUEST['tech_hardware'] == 'cisco') {
        echo '<input type="hidden" name="sccp_device_typeid" value="sccpdevice">';
        if ($dev_id === 'new') {
            echo $this->showGroup('sccp_hw_dev', 1, 'sccp_hw', $def_val);
        } else {
            echo $this->showGroup('sccp_hw_dev_edit', 1, 'sccp_hw', $def_val);
        }
        echo $this->showGroup('sccp_hw_dev2', 1, 'sccp_hw', $def_val);
        echo $this->showGroup('sccp_hw_dev_advance', 1, 'sccp_hw', $def_val);
        echo $this->showGroup('sccp_hw_dev_softkey', 1, 'sccp_hw', $def_val);
        echo $this->showGroup('sccp_hw_dev_conference', 1, 'sccp_hw', $def_val);
        echo $this->showGroup('sccp_dev_vendor_conf', 1, 'vendorconfig', $def_val);
        echo $this->showGroup('sccp_hw_dev_network', 1, 'sccp_hw', $def_val);

    } else if ($_REQUEST['tech_hardware'] == 'cisco-sip') {
        echo '<input type="hidden" name="sccp_device_typeid" value="sipdevice">';
        if ($dev_id === 'new') {
            echo $this->showGroup('sccp_hw_sip_dev', 1, 'sccp_hw', $def_val);
        } else {
            echo $this->showGroup('sccp_hw_dev_edit', 1, 'sccp_hw', $def_val);
        }
        echo $this->showGroup('sccp_hw_sip_dev2', 1, 'sccp_hw', $def_val);
        echo $this->showGroup('sccp_hw_sip_conf', 1, 'sccp_hw', $def_val);
    }
    ?>
</form>
