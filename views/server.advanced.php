<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
?>
<form autocomplete="off" name="frm_advanced" id="frm_ntp" class="fpbx-submit" action="" method="post">
    <input type="hidden" name="category" value="advancedform">
    <input type="hidden" name="Submit" value="Submit">

<?php

        // originally, this was populated by sccpgeneral.xml but that should be static
        // now will populate from the db defaults.
        $defaultVal = array();
        $sccpDeviceDesc = $this->dbinterface->HWextension_db_SccpTableData('get_columns_sccpdevice');

        $translateFieldArray = array('_logserver' => 'vendorconfig_logserver',
                          '_daysdisplaynotactive' => 'vendorconfig_daysdisplaynotactive',
                          '_displayontime' => 'vendorconfig_displayontime',
                          '_displayonduration' => 'vendorconfig_displayonduration',
                          '_displayidletimeout' => 'vendorconfig_displayidletimeout',
                          '_settingsaccess' => 'vendorconfig_settingsaccess',
                          '_videocapability' => 'vendorconfig_videocapability',
                          '_webaccess' => 'vendorconfig_webaccess',
                          '_webadmin' => 'vendorconfig_webadmin',
                          '_pcport' => 'vendorconfig_pcport',
                          '_spantopcport' => 'vendorconfig_spantopcport',
                          '_voicevlanaccess' => 'vendorconfig_voicevlanaccess',
                          '_enablecdpswport' => 'vendorconfig_enablecdpswport',
                          '_enablecdppcport' => 'vendorconfig_enablecdppcport',
                          '_enablelldpswport' => 'vendorconfig_enablelldpswport',
                          '_enablelldppcport' => 'vendorconfig_enablelldppcport'
                        );

        foreach ($sccpDeviceDesc as $data) {
            $key = (string) $data['Field'];
            if (array_key_exists($key, $translateFieldArray)) {
                $defaultVal[$translateFieldArray[$key]] = array("keyword" => $translateFieldArray[$key], "data" => $data['Default'], "seq" => "99");
            }
        }

        echo $this->showGroup('sccp_srst', 1);
        echo $this->showGroup('sccp_dev_vendor_conf', 1,'sccp',$defaultVal,false);
//        echo $this->showGroup('sccp_dev_time',1);

?>
</form>
