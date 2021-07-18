<?php

if (!defined('FREEPBX_IS_AUTH')) {
    die_freepbx('No direct script access allowed');
}

global $db;
global $amp_conf;
global $version;
global $aminterface;
global $extconfigs;
global $mobile_hw;
global $useAmiForSoftKeys;
global $settingsFromDb;
global $thisInstaller;
global $cnf_int;
global $sccp_compatible;
$mobile_hw = '0';
$autoincrement = (($amp_conf["AMPDBENGINE"] == "sqlite") || ($amp_conf["AMPDBENGINE"] == "sqlite3")) ? "AUTOINCREMENT" : "AUTO_INCREMENT";
$table_req = array('sccpdevice', 'sccpline', 'sccpsettings');
$sccp_compatible = 0;
$chanSCCPWarning = true;
$db_config = '';
$sccp_version = array();
$cnf_int = \FreePBX::Config();

// Do not create Sccp_Manager object as not required.
// Only include required classes and create anonymous class for thisInstaller

$thisInstaller = new class{
    use \FreePBX\modules\Sccp_Manager\sccpManTraits\helperFunctions;
};

$requiredClasses = array('aminterface', 'extconfigs');
foreach ($requiredClasses as $className) {
    $class = "\\FreePBX\\Modules\\Sccp_manager\\$className";
    if (!class_exists($class, false)) {
        include(__DIR__ . "/sccpManClasses/$className.class.php");
    }
    if (class_exists($class, false)) {
        ${$className} = new $class();
    }
}

CheckAsteriskVersion();
$sccp_version = CheckChanSCCPCompatible();
$sccp_compatible = $sccp_version[0];
$chanSCCPWarning = $sccp_version[1] ^= 1;
outn("<li>" . _("Sccp model Compatible code : ") . $sccp_compatible . "</li>");
if ($sccp_compatible == 0) {
    outn("<br>");
    outn("<font color='red'>Chan Sccp not Found. Install it before continuing !</font>");
    die();
}

// BackUp Old config
createBackUpConfig();
RenameConfig();

$db_config   = Get_DB_config($sccp_compatible);
InstallDB_updateSchema($db_config);

cleanUpSccpSettings();

InstallDB_createButtonConfigTrigger();
InstallDB_CreateSccpDeviceConfigView($sccp_compatible);
InstallDB_updateDBVer($sccp_compatible);
if ($chanSCCPWarning) {
    outn("<br>");
    outn("<font color='red'>Error: installed version of chan-sccp is not compatible. Please upgrade chan-sccp</font>");
}
Setup_RealTime();
addDriver($sccp_compatible);
checkTftpServer();

outn("<br>");
outn("Install Complete !");
outn("<br>");

// Functions follow

function Get_DB_config($sccp_compatible)
{
    global $mobile_hw;
    // Software mobile
    $db_config_v4 = array(
        'sccpdevmodel' => array(
            'enabled' => array('create' => "INT(2) NULL DEFAULT '0'"),
            'nametemplate' => array('create' => 'VARCHAR(50) NULL DEFAULT NULL'),
            'loadinformationid' => array('create' => "VARCHAR(30) NULL DEFAULT NULL")
        ),
        'sccpdevice' => array(
            'pickupexten' => array('drop' => "yes"),
            'directed_pickup' => array('drop' => "yes"),
            'directed_pickup_context' => array('drop' => "yes"),
            'pickupcontext' => array('drop' => "yes"),
            'directed_pickup_modeanswer' => array('drop' => "yes"),
            'pickupmodeanswer' => array('drop' => "yes"),
            'disallow' => array('drop' => "yes"),
            'callhistory_answered_elsewhere' => array('create' => "enum('Ignore','Missed Calls','Received Calls', 'Placed Calls') NOT NULL default 'Ignore'",
                                                      'modify' => "enum('Ignore','Missed Calls','Received Calls','Placed Calls')"),
            'description' => array('rename' => "_description"),
            'hwlang' => array('rename' => "_hwlang"),
            '_hwlang' => array('create' => 'VARCHAR(12) NULL DEFAULT NULL'),
            '_loginname' => array('create' => 'VARCHAR(20) NULL DEFAULT NULL AFTER `_hwlang`'),
            '_profileid' => array('create' => "INT(11) NOT NULL DEFAULT '0' AFTER `_loginname`"),
            '_dialrules' => array('create' => "VARCHAR(255) NULL DEFAULT NULL AFTER `_profileid`"),
            'useRedialMenu' => array('create' => "enum('yes','no') NOT NULL default 'no'", 'modify' => "enum('yes','no')"),
            'dtmfmode' => array('drop' => "yes"),
            'force_dtmfmode' => array('create' => "ENUM('auto','rfc2833','skinny') NOT NULL default 'auto'",
                          'modify' => "ENUM('auto','rfc2833','skinny')"),
            'deny' => array('create' => 'VARCHAR(100) NULL DEFAULT NULL', 'modify' => "VARCHAR(100)"),
            'permit' => array('create' => 'VARCHAR(100) NULL DEFAULT NULL', 'modify' => "VARCHAR(100)"),
            'backgroundImage' => array('create' => 'VARCHAR(255) NULL DEFAULT NULL', 'modify' => "VARCHAR(255)"),
            'ringtone' => array('create' => 'VARCHAR(255) NULL DEFAULT NULL', 'modify' => "VARCHAR(255)"),
            'transfer' => array('create' => "enum('yes','no') NOT NULL default 'no'", 'modify' => "enum('yes','no')"),
            'cfwdall' => array('create' => "enum('yes','no') NOT NULL default 'yes'", 'modify' => "enum('yes','no')"),
            'cfwdbusy' => array('create' => "enum('yes','no') NOT NULL default 'yes'", 'modify' => "enum('yes','no')"),
            'cfwdnoanswer' => array('create' => "enum('yes','no') NOT NULL default 'yes'", 'modify' => "enum('yes','no')"),
            'park' => array('create' => "enum('on','off') NOT NULL default 'on'", 'modify' => "enum('on','off')"),
            'directrtp' => array('create' => "enum('no','yes') NOT NULL default 'no'", 'modify' => "enum('no','yes')"),
            'dndFeature' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
            'earlyrtp' => array('create' => "ENUM('yes','no') NOT NULL default 'no'", 'modify' => "ENUM('yes','no')"),
            'monitor' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
            'audio_tos' => array('create' => "VARCHAR(11) NOT NULL default '0xB8'",'modify' => "0xB8"),
            'audio_cos' => array('create' => "VARCHAR(11) NOT NULL default '0x6'",'modify' => "0x6"),
            'video_tos' => array('create' => "VARCHAR(11) NOT NULL default '0x88'",'modify' => "0x88"),
            'video_cos' => array('create' => "VARCHAR(11) NOT NULL default '0x5'",'modify' => "0x5"),
            'trustphoneip' => array('drop' => "yes"),
            'transfer_on_hangup' => array('create' => "enum('yes','no') NOT NULL DEFAULT 'no'", 'modify' => "enum('yes','no')"),
            'phonecodepage' => array('create' => 'VARCHAR(50) NULL DEFAULT NULL', 'modify' => "VARCHAR(50)"),
            'mwilamp' => array('create' => "enum('on','off','wink','flash','blink') NOT NULL  default 'on'",
                              'modify' => "enum('on','off','wink','flash','blink')"),
            'mwioncall' => array('create' => "enum('no','yes') NOT NULL default 'yes'",'modify' => "enum('no','yes')"),
            'private' => array('create' => "enum('yes','no') NOT NULL default 'no'", 'modify' => "enum('yes','no')"),
            'privacy' => array('create' => "enum('full','on','off') NOT NULL default 'full'", 'modify' => "enum('full','on','off')"),
            'nat' => array('create' => "enum('on','off','auto') NOT NULL default 'off'", 'modify' => "enum('on','off','auto')"),
            'conf_allow' => array('create' => "enum('on','off') NOT NULL default 'on'", 'modify' => "enum('on','off')"),
            'conf_play_part_announce' => array('create' => "enum('on','off') NOT NULL default 'on'", 'modify' => "enum('on','off')"),
            'conf_mute_on_entry' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
            'conf_show_conflist' => array('create' => "enum('on','off') NOT NULL default 'on'", 'modify' => "enum('on','off')"),
            'type' => array('create' => 'VARCHAR(15) NULL DEFAULT NULL', 'modify' => "VARCHAR(15)"),
            'imageversion' => array('create' => 'VARCHAR(31) NULL DEFAULT NULL', 'modify' => "VARCHAR(31)"),
            'softkeyset' => array('def_modify' => "softkeyset")
        ),
        'sccpline' => array(
            'directed_pickup' => array('create' => "enum('yes','no') NOT NULL default 'no'", 'modify' => "enum('yes','no')"),
            'directed_pickup_context' => array('create' => "VARCHAR(100) NULL DEFAULT NULL"),
            'pickup_modeanswer' => array('create' => "enum('yes','no') NOT NULL default 'yes'", 'modify' => "enum('yes','no')"),
            'namedcallgroup' => array('create' => "VARCHAR(100) NULL DEFAULT NULL AFTER `setvar`", 'modify' => "VARCHAR(100)"),
            'namedpickupgroup' => array('create' => "VARCHAR(100) NULL DEFAULT NULL AFTER `namedcallgroup`", 'modify' => "VARCHAR(100)"),
            'adhocNumber' => array('create' => "VARCHAR(45) NULL DEFAULT NULL AFTER `namedpickupgroup`"),
            'meetme' => array('create' => "VARCHAR(5) NULL DEFAULT NULL AFTER `adhocNumber`"),
            'context' => array('create' => "VARCHAR(45) NULL DEFAULT NULL AFTER `description`", 'def_modify' => 'from-internal'),
            'meetmenum' => array('create' => "VARCHAR(45) NULL DEFAULT NULL AFTER `meetme`"),
            'meetmeopts' => array('create' => "VARCHAR(45) NULL DEFAULT NULL AFTER `meetmenum`"),
            'regexten' => array('create' => "VARCHAR(45) NULL DEFAULT NULL AFTER `meetmeopts`"),
            'rtptos' => array('drop' => "yes"),
            'audio_tos' => array('drop' => "yes"),
            'audio_cos' => array('drop' => "yes"),
            'video_tos' => array('drop' => "yes"),
            'video_cos' => array('drop' => "yes"),
            'videomode' => array('create' => "enum('user','auto','off') NOT NULL default 'auto'", 'modify' => "enum('user','auto','off')"),
            'incominglimit' => array('create' => "INT(11) DEFAULT '6'", 'modify' => 'INT(11)', 'def_modify' => "6"),
            'transfer' => array('create' => "enum('yes','no') NOT NULL default 'yes'", 'modify' => "enum('yes','no')"),
            'vmnum' => array('def_modify' => "*97"),
            'musicclass' => array('def_modify' => "default"),
            'disallow' => array('create' => "VARCHAR(255) NULL DEFAULT NULL"),
            'allow' => array('create' => "VARCHAR(255) NULL DEFAULT NULL"),
            'id' => array('create' => 'MEDIUMINT(9) NOT NULL AUTO_INCREMENT, ADD UNIQUE(id);', 'modify' => "MEDIUMINT(9)", 'index' => 'id'),
            'echocancel' => array('create' => "enum('yes','no') NOT NULL default 'yes'", 'modify' => "enum('yes','no')"),
            'silencesuppression' => array('create' => "enum('yes','no') NOT NULL default 'no'", 'modify' => "enum('yes','no')"),
            'dnd' => array('create' => "enum('off','reject','silent','user') NOT NULL default 'reject'", 'modify' => "enum('off','reject','silent','user')", 'def_modify' => "reject")
        ),
        'sccpuser' => array(
            'name' => array('create' => "VARCHAR(20) NOT NULL", 'modify' => "VARCHAR(20)" ),
            'pin' => array('create' => "VARCHAR(7) NOT NULL", 'modify' => "VARCHAR(7)" ),
            'password' => array('create' => "VARCHAR(7) NOT NULL", 'modify' => "VARCHAR(7)" ),
            'description' => array('create' => "VARCHAR(45) NOT NULL", 'modify' => "VARCHAR(45)" ),
            'roaminglogin' => array('create' => "ENUM('on','off','multi') NOT NULL DEFAULT 'off'", 'modify' => "ENUM('on','off','multi')" ),
            'auto_logout' => array('create' => "ENUM('on','off') NOT NULL DEFAULT 'off'", 'modify' => "ENUM('on','off')" ),
            'homedevice' => array('create' => "VARCHAR(20) NOT NULL", 'modify' => "VARCHAR(20)" ),
            'devicegroup' => array('create' => "VARCHAR(7) NOT NULL", 'modify' => "VARCHAR(7)" ),
        ),
        'sccpbuttonconfig' => array(
            'reftype' => array('create' => "enum('sccpdevice', 'sipdevice', 'sccpuser') NOT NULL default 'sccpdevice'",
                        'modify' => "enum('sccpdevice', 'sipdevice', 'sccpuser')" ),
        )
    );
//  Hardware Mobile.  Can switch Softwate to Hardware
    $db_config_v4M = array(
        'sccpdevmodel' => array(
            'loadinformationid' => array('create' => "VARCHAR(30) NULL DEFAULT NULL")
        ),
        'sccpdevice' => array(
            'pickupexten' => array('drop' => "yes"),
            'directed_pickup' => array('drop' => "yes"),
            'cfwdnoanswer' => array('create' => "enum('yes','no') NULL default 'yes'", 'modify' => "enum('yes','no')"),
            'park' => array('create' => "enum('on','off') NULL default 'on'", 'modify' => "enum('on','off')"),
            'monitor' => array('create' => "enum('on','off') NULL default NULL", 'modify' => "enum('on','off')"),
            '_description' => array('rename' => "description"),
            '_loginname' => array('drop' => "yes"),
            '_profileid' => array('drop' => "yes"),
            '_dialrules' => array('create' => "VARCHAR(255) NULL DEFAULT NULL AFTER `_profileid`"),
            'transfer_on_hangup' => array('create' => "enum('on','off') NULL DEFAULT NULL", 'modify' => "enum('on','off')"),
        ),
        'sccpline' => array(
            'directed_pickup' => array('create' => "enum('on','off') NULL default NULL", 'modify' => "enum('on','off')"),
            'videomode' => array('create' => "enum('user','auto','off') NULL default 'auto'", 'modify' => "enum('user','auto','off')"),
        ),
        'sccpuser' => array(
            'id' => array('create' => "VARCHAR(20) NOT NULL", 'modify' => "VARCHAR(20)" ),
            'name' => array('create' => "VARCHAR(45) NOT NULL", 'modify' => "VARCHAR(45)" ),
        )
    );
    // Below fields allow configuration of these settings on a per device basis
    // whereas previously they were all global.
    // By prefixing with an underscore, these fields are ignored by chan-sccp
    // which is not an issue as they are not "runtime"
    $db_config_v5 = array(
        'sccpdevice' => array(
              '_logserver' => array('create' => "VARCHAR(100) NULL default null", 'modify' => "VARCHAR(20)"),
              '_daysdisplaynotactive' => array('create' => "VARCHAR(20) NULL default null", 'modify' => "VARCHAR(20)"),
              '_displayontime' => array('create' => "VARCHAR(20) NULL default null", 'modify' => "VARCHAR(20)"),
              '_displayonduration' => array('create' => "VARCHAR(20) NULL default null", 'modify' => "VARCHAR(20)"),
              '_displayidletimeout' => array('create' => "VARCHAR(20) NULL default null", 'modify' => "VARCHAR(20)"),
              '_settingsaccess' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
              '_videocapability' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
              '_webaccess' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
              '_webadmin' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
              '_pcport' => array('create' => "enum('on','off') NOT NULL default 'on'", 'modify' => "enum('on','off')"),
              '_spantopcport' => array('create' => "enum('on','off') NOT NULL default 'on'", 'modify' => "enum('on','off')"),
              '_voicevlanaccess' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
              '_enablecdpswport' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
              '_enablecdppcport' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
              '_enablelldpswport' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
              '_enablelldppcport' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
              '_firstdigittimeout' => array('create' => "VARCHAR(20) NULL default null", 'modify' => "VARCHAR(20)"),
              '_digittimeout' => array('create' => "VARCHAR(20) NULL default null", 'modify' => "VARCHAR(20)"),
              '_cfwdnoanswer_timeout' => array('create' => "VARCHAR(20) NULL default null", 'modify' => "VARCHAR(20)"),
              '_autoanswer_ring_time' => array('create' => "VARCHAR(20) NULL default null", 'modify' => "VARCHAR(20)"),
              '_autoanswer_tone' => array('create' => "VARCHAR(20) NULL default null", 'modify' => "VARCHAR(20)"),
              '_remotehangup_tone' => array('create' => "VARCHAR(20) NULL default null", 'modify' => "VARCHAR(20)"),
              '_transfer_tone' => array('create' => "VARCHAR(20) NULL default null", 'modify' => "VARCHAR(20)"),
              '_callwaiting_tone' => array('create' => "VARCHAR(20) NULL default null", 'modify' => "VARCHAR(20)"),
              '_callanswerorder' => array('create' => "enum('oldestfirst','latestfirst') NOT NULL default 'latestfirst'",
                                    'modify' => "enum('oldestfirst','latestfirst')"),
              '_sccp_tos' => array('create' => "VARCHAR(11) NOT NULL default '0x68'", 'modify' => "VARCHAR(11)"),
              '_sccp_cos' => array('create' => "VARCHAR(11) NOT NULL default '0x4'", 'modify' => "VARCHAR(11)"),
              '_dev_sshPassword' => array('create' => "VARCHAR(25) NOT NULL default 'cisco'"),
              '_dev_sshUserId' => array('create' => "VARCHAR(25) NOT NULL default 'cisco'"),
              '_phonepersonalization' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')")
            ),
        'sccpline' => array (
              '_regcontext' => array('create' => "VARCHAR(20) NULL default 'sccpregistration'", 'modify' => "VARCHAR(20)"),
              '_transfer_on_hangup' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
              '_autoselectline_enabled' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
              '_autocall_select' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
              '_backgroundImageAccess' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
              '_callLogBlfEnabled' => array('create' => "enum('3','2') NOT NULL default '2'", 'modify' => "enum('3','2')")
            ),
        'sccpsettings' => array (
              'systemdefault' => array('create' => "VARCHAR(255) NULL default ''")
        )
    );

    if ($sccp_compatible >= 433) {
        if ($mobile_hw == '1') {
            return $db_config_v4M;
        }
        // This looks extraneous, but is for future compatibility - do not delete
        // If integrated into chan-sccp, the version number will change
        if ($sccp_compatible >= 433) {
            $db_config_v4['sccpdevice'] = array_merge($db_config_v4['sccpdevice'],$db_config_v5['sccpdevice']);
            $db_config_v4['sccpline'] = array_merge($db_config_v4['sccpline'],$db_config_v5['sccpline']);
            $db_config_v4['sccpsettings'] = $db_config_v5['sccpsettings'];
        }
        return $db_config_v4;
    }

    // New values to add (these are currently unused)


}

function CheckSCCPManagerDBVersion()
{

}

/* notused */

function CheckPermissions()
{
    outn("<li>" . _("Checking Filesystem Permissions") . "</li>");
    $dst = $_SERVER['DOCUMENT_ROOT'] . '/admin/modules/sccp_manager/views';
    if (fileowner($_SERVER['DOCUMENT_ROOT']) != fileowner($dst)) {
        die_freepbx('Please (re-)check permissions by running "amportal chown. Installation Failed"');
    }
}

function CheckAsteriskVersion()
{
    $version = FreePBX::Config()->get('ASTVERSION');
    outn("<li>" . _("Checking Asterisk Version : ") . $version . "</li>");
    if (!empty($version)) {
        // Woo, we have a version
        if (version_compare($version, "12.2.0", ">=")) {
            $ver_compatible = true;
        } else {
            die_freepbx('Asterisk Version is to old, please upgrade to asterisk-12 or higher. Installation Failed');
        }
    } else {
        // Well. I don't know what version of Asterisk I'm running.
        // Assume less than 12.
        $ver_compatible = false;
        die_freepbx('Asterisk Version could not be verified. Installation Failed');
    }
    return $ver_compatible;
}

function CheckChanSCCPCompatible()
{
    global $chanSCCPWarning;
    global $aminterface;
    // calling with true returns array with compatibility and RevisionNumber
    return $aminterface->get_compatible_sccp(true);
}

function InstallDB_updateSchema($db_config)
{
    global $db;
    if (!$db_config) {
        die_freepbx("No db_config provided");
    }
    $count_modify = 0;
    outn("<li>" . _("Modify Database schema") . "</li>");
    foreach ($db_config as $tabl_name => &$tab_modif) {
        // 0 - name 1-type  4- default
        $sql = "DESCRIBE {$tabl_name};";
        $stmt = $db->prepare("DESCRIBE {$tabl_name}");
        $stmt->execute();
        $db_result = $stmt->fetchAll();
        if (DB::IsError($db_result)) {
            die_freepbx("Can not get information from " . $tabl_name . " table\n");
        }
        foreach ($db_result as $tabl_data) {
            $fld_id = $tabl_data[0];
            $db_config[$tabl_name][$fld_id]['fieldExists'] = FALSE;
            // Filter commands to avoid applying unnecessary
            if (!empty($tab_modif[$fld_id])) {
                // Potentially have something to modify in schema
                $db_config[$tabl_name][$fld_id]['fieldExists'] = TRUE;
                if (!empty($tab_modif[$fld_id]['modify'])) {
                    if (strtoupper($tab_modif[$fld_id]['modify']) == strtoupper($tabl_data[1])) {
                        unset($db_config[$tabl_name][$fld_id]['modify']);
                    }
                    // Modifying field so do not then need to modify defaults as this should do that
                    if (!empty($tab_modif[$fld_id]['def_modify'])) {
                            unset($db_config[$tabl_name][$fld_id]['def_modify']);
                    }
                }
                if (!empty($tab_modif[$fld_id]['def_modify'])) {
                    if (strtoupper($tab_modif[$fld_id]['def_modify']) == strtoupper($tabl_data[4])) {
                        unset($db_config[$tabl_name][$fld_id]['def_modify']);
                    }
                }
                if (!empty($tab_modif[$fld_id]['rename'])) {
                    $fld_id_source = $tab_modif[$fld_id]['rename'];
                    $db_config[$tabl_name][$fld_id_source]['fieldExists'] = TRUE;
                    if (!empty($db_config[$tabl_name][$fld_id_source]['create'])) {
                        $db_config[$tabl_name][$fld_id]['create'] = $db_config[$tabl_name][$fld_id_source]['create'];
                    } else {
                        $db_config[$tabl_name][$fld_id]['create'] = strtoupper($tabl_data[1]).(($tabl_data[2] == 'NO') ?' NOT NULL': ' NULL');
                        $db_config[$tabl_name][$fld_id]['create'] .= ' DEFAULT '. ((empty($tabl_data[4]))?'NULL': "'". $tabl_data[4]."'" );
                    }
                }
            }
        }
        $sql_create = '';
        $sql_modify = '';
        $sql_update = '';

        foreach ($tab_modif as $row_fld => $row_data) {
            if (!$row_data['fieldExists']) {
                if (!empty($row_data['create'])) {
                    $sql_create .= "ADD COLUMN {$row_fld} {$row_data['create']}, ";
                    $count_modify ++;
                }
            } else {
                if (!empty($row_data['rename'])) {
                    $sql_modify .= "RENAME COLUMN  {$row_fld} TO {$row_data['rename']}, ";
                    $count_modify ++;
                }
                $row_data['fieldModified'] = FALSE;
                if (!empty($row_data['modify'])) {
                    if (!empty($row_data['create'])) {
                        // Use values in create to set defaults
                        $sql_modify .= "MODIFY COLUMN {$row_fld}  {$row_data['create']}, ";
                    } else {
                        $sql_modify .= "MODIFY COLUMN {$row_fld} {$row_data['modify']} DEFAULT {$row_data['def_modify']}, ";
                    }
                    if (strpos($row_data['modify'], 'enum') !== false) {
                        $sql_update .= "UPDATE " . $tabl_name . " set `" . $row_fld . "`=case when lower(`" . $row_fld . "`) in ('yes','true','1') then 'on' when lower(`" . $row_fld . "`) in ('no', 'false', '0') then 'off' else `" . $row_fld . "` end; ";
                    }
                    $count_modify ++;
                }
                if (!empty($row_data['def_modify'])) {
                    $sql_modify .= "ALTER COLUMN {$row_fld} SET DEFAULT '{$row_data['def_modify']}', ";
                    $count_modify ++;
                }
                if (!empty($row_data['drop'])) {
                    $sql_create .= "DROP COLUMN {$row_fld}, ";
                    $count_modify ++;
                }
            }
        }
        if (!empty($sql_update)) {
            outn("<li>" . _("Updating table rows :") . $affected_rows . "</li>");
            $sql_update = 'BEGIN; ' . $sql_update . ' COMMIT;';
            sql($sql_update);
            $affected_rows = $db->affectedRows();
            outn("<li>" . _("Updated table rows :") . $affected_rows . "</li>");
        }

        if (!empty($sql_create)) {
            outn("<li>" . _("Adding new columns ...") . "</li>");
            $sql_create = "ALTER TABLE {$tabl_name} " .substr($sql_create, 0, -2);
            try {
            $check = $db->query($sql_create);
            } catch (\Exception $e) {
                die_freepbx("Can't add column to {$tabl_name}. SQL:  {$sql_create} \n");
            }
        }
        if (!empty($sql_modify)) {
            outn("<li>" . _("Modifying table columns ") . $tabl_name ."</li>");

            $sql_modify = "ALTER TABLE {$tabl_name} " . substr($sql_modify, 0, -2);
            try {
                $check = $db->query($sql_modify);
            } catch (\Exception $e) {
                  die_freepbx("Can not modify {$tabl_name}. SQL:  {$sql_create} \n");
            }
        }
    }
    outn("<li>" . _("Total modify count :") . $count_modify . "</li>");

    $stmt = $db->prepare('SELECT CASE WHEN EXISTS(SELECT 1 FROM sccpdevmodel) THEN 0 ELSE 1 END AS IsEmpty;');
    $stmt->execute();
    $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    if (!$result[0]['IsEmpty']) {
        return;
    } else {
        outn("Populating sccpdevmodel...");
        outn("<li>" . _("Fill sccpdevmodel") . "</li>");
        $sql = "REPLACE INTO sccpdevmodel (model, vendor, dns, buttons, loadimage, loadinformationid, enabled, nametemplate) VALUES
                  ('12 SP', 'CISCO', 1, 1, '', 'loadInformation3', 0, NULL),
                  ('12 SP+', 'CISCO', 1, 1, '', 'loadInformation2', 0, NULL),
                  ('30 SP+', 'CISCO', 1, 1, '', 'loadInformation1', 0, NULL),
                  ('30 VIP', 'CISCO', 1, 1, '', 'loadInformation5', 0, NULL),
                  ('3911', 'CISCO', 1, 1, '', 'loadInformation446', 0, NULL),
                  ('3951', 'CISCO', 1, 1, '', 'loadInformation412', 0, ''),
                  ('6901', 'CISCO', 1, 1, 'SCCP6901.9-2-1-a', 'loadInformation547', 0, NULL),
                  ('6911', 'CISCO', 1, 1, 'SCCP6911.9-2-1-a', 'loadInformation548', 0, NULL),
                  ('6921', 'CISCO', 1, 1, 'SCCP69xx.9-4-1-3SR3', 'loadInformation496', 0, NULL),
                  ('6941', 'CISCO', 1, 1, 'SCCP69xx.9-3-1-3', 'loadInformation495', 0, NULL),
                  ('6945', 'CISCO', 1, 1, 'SCCP6945.9-3-1-3', 'loadInformation564', 0, NULL),
                  ('6961', 'CISCO', 1, 1, 'SCCP69xx.9-2-1-0', 'loadInformation497', 0, NULL),
                  ('7902', 'CISCO', 1, 1, 'CP7902080002SCCP060817A', 'loadInformation30008', 0, NULL),
                  ('7905', 'CISCO', 1, 1, 'CP7905080003SCCP070409A', 'loadInformation20000', 0, NULL),
                  ('7906', 'CISCO', 1, 1, 'SCCP11.9-4-2SR3-1S', 'loadInformation369', 1, 'SEP0000000000.cnf.xml_791x_template'),
                  ('7910', 'CISCO', 1, 1, 'P00405000700', 'loadInformation6', 1, 'SEP0000000000.cnf.xml_791x_template'),
                  ('7911', 'CISCO', 1, 1, 'SCCP11.9-4-2SR3-1S', 'loadInformation307', 1, 'SEP0000000000.cnf.xml_791x_template'),
                  ('7912', 'CISCO', 1, 1, 'CP7912080004SCCP080108A', 'loadInformation30007', 0, NULL),
                  ('7914', 'CISCO', 0, 14, 'S00105000400', 'loadInformation124', 1, NULL),
                  ('7914;7914', 'CISCO', 0, 28, 'S00105000400', 'loadInformation124', 1, NULL),
                  ('7915', 'CISCO', 0, 24, 'B015-1-0-4-2', 'loadInformation227', 1, NULL),
                  ('7915;7915', 'CISCO', 0, 48, 'B015-1-0-4-2', 'loadInformation228', 1, NULL),
                  ('7916', 'CISCO', 0, 24, 'B016-1-0-4-2', 'loadInformation229', 1, NULL),
                  ('7916;7916', 'CISCO', 0, 48, 'B016-1-0-4-2', 'loadInformation230', 1, NULL),
                  ('7920', 'CISCO', 1, 1, 'cmterm_7920.4.0-03-02', 'loadInformation30002', 0, NULL),
                  ('7921', 'CISCO', 1, 1, 'CP7921G-1.4.6.3', 'loadInformation365', 0, NULL),
                  ('7925', 'CISCO', 1, 6, 'CP7925G-1.4.1SR1', 'loadInformation484', 0, 'SEP0000000000.cnf.xml_7925_template'),
                  ('7926', 'CISCO', 1, 1, 'CP7926G-1.4.1SR1', 'loadInformation557', 0, NULL),
                  ('7931', 'CISCO', 1, 34, 'SCCP31.9-2-1S', 'loadInformation348', 0, NULL),
                  ('7935', 'CISCO', 1, 2, 'P00503021900', 'loadInformation9', 0, NULL),
                  ('7936', 'CISCO', 1, 1, 'cmterm_7936.3-3-21-0', 'loadInformation30019', 0, NULL),
                  ('7937', 'CISCO', 1, 1, 'apps37sccp.1-4-5-7', 'loadInformation431', 0, 'SEP0000000000.cnf.xml_7937_template'),
                  ('7940', 'CISCO', 1, 2, 'P0030801SR02', 'loadInformation8', 1, 'SEP0000000000.cnf.xml_7940_template'),
                  ('7941', 'CISCO', 1, 2, 'SCCP41.9-4-2SR3-1S', 'loadInformation115', 0, 'SEP0000000000.cnf.xml_796x_template'),
                  ('7941G-GE', 'CISCO', 1, 2, 'SCCP41.9-4-2SR3-1S', 'loadInformation309', 0, 'SEP0000000000.cnf.xml_796x_template'),
                  ('7942', 'CISCO', 1, 2, 'SCCP42.9-4-2SR3-1S', 'loadInformation434', 0, 'SEP0000000000.cnf.xml_796x_template'),
                  ('7945', 'CISCO', 1, 2, 'SCCP45.9-3-1SR1-1S', 'loadInformation435', 0, 'SEP0000000000.cnf.xml_796x_template'),
                  ('7960', 'CISCO', 3, 6, 'P0030801SR02', 'loadInformation7', 1, 'SEP0000000000.cnf.xml_7940_template'),
                  ('7961', 'CISCO', 3, 6, 'SCCP41.9-4-2SR3-1S', 'loadInformation30018', 0, 'SEP0000000000.cnf.xml_796x_template'),
                  ('7961G-GE', 'CISCO', 3, 6, 'SCCP41.9-4-2SR3-1S', 'loadInformation308', 0, 'SEP0000000000.cnf.xml_796x_template'),
                  ('7962', 'CISCO', 3, 6, 'SCCP42.9-4-2SR3-1S', 'loadInformation404', 0, 'SEP0000000000.cnf.xml_796x_template'),
                  ('7965', 'CISCO', 3, 6, 'SCCP45.9-3-1SR1-1S', 'loadInformation436', 0, 'SEP0000000000.cnf.xml_796x_template'),
                  ('7970', 'CISCO', 3, 8, 'SCCP70.9-4-2SR3-1S', 'loadInformation30006', 0, 'SEP0000000000.cnf.xml_797x_template'),
                  ('7971', 'CISCO', 1, 2, 'SCCP70.9-4-2SR3-1S', 'loadInformation119', 0, 'SEP0000000000.cnf.xml_797x_template'),
                  ('7975', 'CISCO', 3, 8, 'SCCP75.9-4-2SR3-1S', 'loadInformation437', 0, 'SEP0000000000.cnf.xml_7975_template'),
                  ('7985', 'CISCO', 3, 8, 'cmterm_7985.4-1-7-0', 'loadInformation302', 0, NULL),
                  ('8941', 'CISCO', 1, 1, 'SCCP894x.9-2-2-0', 'loadInformation586', 0, NULL),
                  ('8945', 'CISCO', 1, 1, 'SCCP894x.9-2-2-0', 'loadInformation585', 0, NULL),
                  ('ATA 186', 'CISCO', 1, 1, 'ATA030204SCCP090202A', 'loadInformation12', 0, 'SEP0000000000.cnf.xml_ATA_template'),
                  ('ATA 187', 'CISCO', 1, 1, 'ATA187.9-2-3-1', 'loadInformation550', 0, 'SEP0000000000.cnf.xml_ATA_template'),
                  ('CN622', 'MOTOROLA', 1, 1, '', 'loadInformation335', 0, NULL),
                  ('Digital Access', 'CISCO', 1, 1, 'D001M022', 'loadInformation40', 0, NULL),
                  ('Digital Access+', 'CISCO', 1, 1, 'D00303010033', 'loadInformation42', 0, NULL),
                  ('E-Series', 'NOKIA', 1, 1, '', '', 0, NULL),
                  ('ICC', 'NOKIA', 1, 1, '', '', 0, NULL),
                  ('Analog Access', 'CISCO', 1, 1, 'A001C030', 'loadInformation30', 0, ''),('WS-X6608', 'CISCO', 1, 1, 'D00404000032', 'loadInformation43', 0, ''),
                  ('WS-X6624', 'CISCO', 1, 1, 'A00204000013', 'loadInformation43', 0, ''),
                  ('WS-X6608', 'CISCO', 1, 1, 'C00104000003', 'loadInformation51', 0, ''),
                  ('H.323 Phone', 'CISCO', 1, 1, '', 'loadInformation61', 0, ''),
                  ('Simulator', 'CISCO', 1, 1, '', 'loadInformation100', 0, ''),
                  ('MTP', 'CISCO', 1, 1, '', 'loadInformation111', 0, ''),
                  ('MGCP Station', 'CISCO', 1, 1, '', 'loadInformation120', 0, ''),
                  ('MGCP Trunk', 'CISCO', 1, 1, '', 'loadInformation121', 0, ''),
                  ('UPC', 'CISCO', 1, 1, '', 'loadInformation358', 0, ''),
                  ('TelePresence', 'TELEPRESENCE', 1, 1, '', 'loadInformation375', 0, ''),
                  ('1000', 'TELEPRESENCE', 1, 1, '', 'loadInformation478', 0, ''),
                  ('3000', 'TELEPRESENCE', 1, 1, '', 'loadInformation479', 0, ''),
                  ('3200', 'TELEPRESENCE', 1, 1, '', 'loadInformation480', 0, ''),
                  ('500-37', 'TELEPRESENCE', 1, 1, '', 'loadInformation481', 0, ''),
                  ('1300-65', 'TELEPRESENCE', 1, 1, '', 'loadInformation505', 0, ''),
                  ('1100', 'TELEPRESENCE', 1, 1, '', 'loadInformation520', 0, ''),
                  ('200', 'TELEPRESENCE', 1, 1, '', 'loadInformation557', 0, ''),
                  ('400', 'TELEPRESENCE', 1, 1, '', 'loadInformation558', 0, ''),
                  ('EX90', 'TELEPRESENCE', 1, 1, '', 'loadInformation584', 0, ''),
                  ('500-32', 'TELEPRESENCE', 1, 1, '', 'loadInformation590', 0, ''),
                  ('1300-47', 'TELEPRESENCE', 1, 1, '', 'loadInformation591', 0, ''),
                  ('TX1310-65', 'TELEPRESENCE', 1, 1, '', 'loadInformation596', 0, ''),
                  ('EX60', 'TELEPRESENCE', 1, 1, '', 'loadInformation604', 0, ''),
                  ('C90', 'TELEPRESENCE', 1, 1, '', 'loadInformation606', 0, ''),
                  ('C60', 'TELEPRESENCE', 1, 1, '', 'loadInformation607', 0, ''),
                  ('C40', 'TELEPRESENCE', 1, 1, '', 'loadInformation608', 0, ''),
                  ('C20', 'TELEPRESENCE', 1, 1, '', 'loadInformation609', 0, ''),
                  ('C20-42', 'TELEPRESENCE', 1, 1, '', 'loadInformation610', 0, ''),
                  ('C60-42', 'TELEPRESENCE', 1, 1, '', 'loadInformation611', 0, ''),
                  ('C40-52', 'TELEPRESENCE', 1, 1, '', 'loadInformation612', 0, ''),
                  ('C60-52', 'TELEPRESENCE', 1, 1, '', 'loadInformation613', 0, ''),
                  ('C60-52D', 'TELEPRESENCE', 1, 1, '', 'loadInformation614', 0, ''),
                  ('C60-65', 'TELEPRESENCE', 1, 1, '', 'loadInformation615', 0, ''),
                  ('C90-65', 'TELEPRESENCE', 1, 1, '', 'loadInformation616', 0, ''),
                  ('MX200', 'TELEPRESENCE', 1, 1, '', 'loadInformation617', 0, ''),
                  ('TX9000', 'TELEPRESENCE', 1, 1, '', 'loadInformation619', 0, ''),
                  ('TX9200', 'TELEPRESENCE', 1, 1, '', 'loadInformation620', 0, ''),
                  ('SX20', 'TELEPRESENCE', 1, 1, '', 'loadInformation626', 0, ''),
                  ('MX300', 'TELEPRESENCE', 1, 1, '', 'loadInformation627', 0, ''),
                  ('C40-42', 'TELEPRESENCE', 1, 1, '', 'loadInformation633', 0, ''),
                  ('Jabber', 'CISCO', 1, 1, '', 'loadInformation652', 0, ''),
                  ('S60', 'NOKIA', 0, 1, '', 'loadInformation376', 0, ''),
                  ('9971', 'CISCO', 1, 1, '', 'loadInformation493', 0, ''),
                  ('9951', 'CISCO', 1, 1, '', 'loadInformation537', 0, ''),
                  ('8961', 'CISCO', 1, 1, '', 'loadInformation540', 0, ''),
                  ('Iphone', 'APPLE', 0, 1, '', 'loadInformation562', 0, ''),
                  ('Android', 'ANDROID', 0, 1, '', 'loadInformation575', 0, ''),
                  ('7926', 'CISCO', 1, 1, 'CP7926G-1.4.5.3', 'loadInformation577', 0, ''),
                  ('7821', 'CISCO', 1, 1, '', 'loadInformation621', 0, ''),
                  ('7841', 'CISCO', 1, 1, '', 'loadInformation622', 0, ''),
                  ('7861', 'CISCO', 1, 1, '', 'loadInformation623', 0, ''),
                  ('VXC 6215', 'CISCO', 1, 1, '', 'loadInformation634', 0, ''),
                  ('8831', 'CISCO', 1, 1, '', 'loadInformation659', 0, ''),
                  ('8841', 'CISCO', 1, 1, '', 'loadInformation683', 0, ''),
                  ('8851', 'CISCO', 1, 1, '', 'loadInformation684', 0, ''),
                  ('8861', 'CISCO', 1, 1, '', 'loadInformation685', 0, ''),
                  ('Analog', 'CISCO', 1, 1, '', 'loadInformation30027', 0, ''),
                  ('ISDN', 'CISCO', 1, 1, '', 'loadInformation30028', 0, ''),
                  ('SCCP GW', 'CISCO', 1, 1, '', 'loadInformation30032', 0, ''),
                  ('IP-STE', 'CISCO', 1, 1, '', 'loadInformation30035', 0, ''),
                  ('SPA 521S', 'CISCO', 1, 1, '', 'loadInformation80000', 0, ''),
                  ('SPA 502G', 'CISCO', 1, 1, '', 'loadInformation80003', 0, ''),
                  ('SPA 504G', 'CISCO', 1, 1, '', 'loadInformation80004', 0, ''),
                  ('SPA 525G', 'CISCO', 1, 1, '', 'loadInformation80005', 0, ''),
                  ('SPA 525G2', 'CISCO', 1, 1, '', 'loadInformation80009', 0, ''),
                  ('SPA 303G', 'CISCO', 1, 1, '', 'loadInformation80011', 0, ''),
                  ('IP Communicator', 'CISCO', 1, 1, '', 'loadInformation30016', 0, NULL),
                  ('Nokia E', 'Nokia', 1, 28, '', 'loadInformation275', 0, NULL),
                  ('VGC Phone', 'CISCO', 1, 1, '', 'loadInformation10', 0, NULL),
                  ('7911-sip', 'CISCO-SIP', 1, 1, 'SIP11.9-2-1S', 'loadInformation307', 1, 'SEP0000000000.cnf.xml_791x_sip_template'),
                  ('9951-sip', 'CISCO-SIP', 1, 1, 'sip9951.9-2-2SR1-9', 'loadinformation537', 1, 'SEP0000000000.cnf.xml_99xx_sip_template'),
                  ('VGC Virtual', 'CISCO', 1, 1, '', 'loadInformation11', 0, NULL);";
        $check = $db->query($sql);
        if (DB::IsError($check)) {
            die_freepbx("Can not create sccpdevmodel table, error:$check\n");
        }
    }
    return;
}

function InstallDB_createButtonConfigTrigger()
{
    global $db;
    outn("<li>" . _("(Re)Create buttonconfig trigger") . "</li>");
    $sql = "DROP TRIGGER IF EXISTS sccp_trg_buttonconfig;";

    $sql .= "CREATE TRIGGER `sccp_trg_buttonconfig` BEFORE INSERT ON `sccpbuttonconfig` FOR EACH ROW BEGIN
        IF NEW.`reftype` = 'sccpdevice' THEN
            IF (SELECT COUNT(*) FROM `sccpdevice` WHERE `sccpdevice`.`name` = NEW.`ref` ) = 0 THEN
                UPDATE `Foreign key contraint violated: ref does not exist in sccpdevice` SET x=1;
            END IF;
        END IF;
        IF NEW.`reftype` = 'sccpline' THEN
            IF (SELECT COUNT(*) FROM `sccpline` WHERE `sccpline`.`name` = NEW.`ref`) = 0 THEN
                UPDATE `Foreign key contraint violated: ref does not exist in sccpline` SET x=1;
            END IF;
        END IF;
        IF NEW.`buttontype` = 'line' THEN
            SET @line_x = SUBSTRING_INDEX(NEW.`name`,'!',1);
            SET @line_x = SUBSTRING_INDEX(@line_x,'@',1);
            IF NEW.`reftype` != 'sipdevice' THEN
                IF (SELECT COUNT(*) FROM `sccpline` WHERE `sccpline`.`name` = @line_x ) = 0 THEN
                    UPDATE `Foreign key contraint violated: line does not exist in sccpline` SET x=1;
                END IF;
            END IF;
        END IF;
        END;";
    $check = $db->query($sql);
    if (DB::IsError($check)) {
        die_freepbx("Can not modify sccpdevice table\n");
    }
    outn("<li>" . _("(Re)Create trigger Ok") . "</li>");
    return true;
}
function InstallDB_updateDBVer($sccp_compatible)
{
    global $db;
    outn("<li>" . _("Update DB Ver") . "</li>");
    $sql = "REPLACE INTO `sccpsettings` (`keyword`, `data`, `seq`, `type`) VALUES ('SccpDBmodel', '". $sccp_compatible. "','30','0');";
    $results = $db->query($sql);
    if (DB::IsError($results)) {
        die_freepbx(sprintf(_("Error updating sccpsettings. Command was: %s; error was: %s "), $sql, $results->getMessage()));
    }
    return true;
}

function InstallDB_CreateSccpDeviceConfigView($sccp_compatible)
{
    global $db;
    outn("<li>" . _("(Re)Create sccpdeviceconfig view") . "</li>");
    $sql = "";
    $sql = "DROP VIEW IF EXISTS sccpdeviceconfig;
            DROP VIEW IF EXISTS sccpuserconfig;";
    ///    global $hw_mobil;
    // From logserver to end only applies to db ver > 433

    global $mobile_hw;
    if ($mobile_hw == '1') {
        $sql .= "CREATE OR REPLACE
            ALGORITHM = MERGE
            VIEW sccpdeviceconfig AS
            SELECT GROUP_CONCAT( CONCAT_WS( ',', sccpbuttonconfig.buttontype, sccpbuttonconfig.name, sccpbuttonconfig.options )
            ORDER BY instance ASC SEPARATOR ';' ) AS sccpbutton, sccpdevice.*
            FROM sccpdevice
            LEFT JOIN sccpbuttonconfig ON (sccpbuttonconfig.reftype = 'sccpdevice' AND sccpbuttonconfig.ref = sccpdevice.name )
            GROUP BY sccpdevice.name; ";
            $sql .=  "CREATE OR REPLACE ALGORITHM = MERGE VIEW sccpuserconfig AS
            SELECT GROUP_CONCAT( CONCAT_WS( ',', sccpbuttonconfig.buttontype, sccpbuttonconfig.name, sccpbuttonconfig.options )
            ORDER BY instance ASC SEPARATOR ';' ) AS button, sccpuser.*
            FROM sccpuser
            LEFT JOIN sccpbuttonconfig ON ( sccpbuttonconfig.reftype = 'sccpuser' AND sccpbuttonconfig.ref = sccpuser.id)
            GROUP BY sccpuser.name; ";
    } else {
        $sql .= "CREATE OR REPLACE
            ALGORITHM = MERGE
            VIEW sccpdeviceconfig AS
            SELECT  case sccpdevice._profileid
                when 0 then
        		(select GROUP_CONCAT(CONCAT_WS( ',', defbutton.buttontype, defbutton.name, defbutton.options ) SEPARATOR ';') from sccpbuttonconfig as defbutton where defbutton.ref = sccpdevice.name ORDER BY defbutton.instance )
                when 1 then
        		(select GROUP_CONCAT(CONCAT_WS( ',', userbutton.buttontype, userbutton.name, userbutton.options ) SEPARATOR ';') from sccpbuttonconfig as userbutton where userbutton.ref = sccpdevice._loginname ORDER BY userbutton.instance )
                when 2 then
	         (select GROUP_CONCAT(CONCAT_WS( ',', homebutton.buttontype, homebutton.name, homebutton.options ) SEPARATOR ';') from sccpbuttonconfig as homebutton where homebutton.ref = sccpuser.homedevice  ORDER BY homebutton.instance )
                end as button,  if(sccpdevice._profileid = 0, sccpdevice._description, sccpuser.description) as description, sccpdevice.*
            FROM sccpdevice
            LEFT JOIN sccpuser sccpuser ON ( sccpuser.name = sccpdevice._loginname )
            GROUP BY sccpdevice.name;";
    }

    $results = $db->query($sql);
    if (DB::IsError($results)) {
        die_freepbx(sprintf(_("Error updating sccpdeviceconfig view. Command was: %s; error was: %s "), $sql, $results->getMessage()));
    }
    return true;
}
function createBackUpConfig()
{
    global $amp_conf;
    global $cnf_int;
    outn("<li>" . _("Creating Config BackUp") . "</li>");
    $backup_files = array('extensions','extconfig','res_mysql', 'res_config_mysql','sccp','sccp_hardware','sccp_extensions');
    $backup_ext = array('_custom.conf', '_additional.conf','.conf');
    $dir = $cnf_int->get('ASTETCDIR');

    $fsql = $dir.'/sccp_backup_'.date("Ymd").'.sql';
    $result = exec('mysqldump '.$amp_conf['AMPDBNAME'].' --password='.$amp_conf['AMPDBPASS'].' --user='.$amp_conf['AMPDBUSER'].' --single-transaction >'.$fsql);

    try {
        $zip = new \ZipArchive();
    } catch (\Exception $e) {
        outn("<br>");
        outn("<font color='red'>PHPx.x-zip not installed where x.x is the installed PHP version. Install it before continuing !</font>");
        die_freepbx();
    }
    $filename = $dir . "/sccp_install_backup" . date("Ymd"). ".zip";
    if ($zip->open($filename, \ZIPARCHIVE::CREATE)) {
        foreach ($backup_files as $file) {
            foreach ($backup_ext as $b_ext) {
                if (file_exists($dir . '/'.$file . $b_ext)) {
                    $zip->addFile($dir . '/'.$file . $b_ext);
                }
            }
        }
        if (file_exists($fsql)) {
            $zip->addFile($fsql);
        }
        $zip->close();
    } else {
        outn("<li>" . _("Error Creating BackUp: ") . $filename ."</li>");
    }
    unlink($fsql);
    outn("<li>" . _("Config backup created: ") . $filename ."</li>");
}

function RenameConfig()
{
    outn("<li>" . _("Move Old Config") . "</li>");
    global $cnf_int;
    $rename_files = array('sccp_hardware','sccp_extensions');
    $rename_ext = array('_custom.conf', '_additional.conf','.conf');
    $dir = $cnf_int->get('ASTETCDIR');
    foreach ($rename_files as $file) {
        foreach ($rename_ext as $b_ext) {
            if (file_exists($dir . '/'.$file . $b_ext)) {
                rename($dir . '/'.$file . $b_ext, $dir . '/'.$file . $b_ext.'.old');
            }
        }
    }
}

function Setup_RealTime()
{
    outn("<li>" . _("Checking realtime configuration ...") . "</li>");
    global $amp_conf;
    global $cnf_int;
    $cnf_wr = \FreePBX::WriteConfig();
    $cnf_read = \FreePBX::LoadConfig();

    // Define required default settings based on FreePBX and system settings
    $dir = $cnf_int->get('ASTETCDIR');
    $sys_mysql_socket = ini_get('pdo_mysql.default_socket');
    $def_bd_config = array('dbhost' => $amp_conf['AMPDBHOST'],
                            'dbname' => $amp_conf['AMPDBNAME'],
                            'dbuser' => $amp_conf['AMPDBUSER'],
                            'dbpass' => $amp_conf['AMPDBPASS'],
                            'dbport' => '3306',
                            'dbsock' => '/var/lib/mysql/mysql.sock',
                            'dbcharset'=>'utf8'
                          );
    if (!empty($sys_mysql_socket)) {
        if (file_exists($sys_mysql_socket)) {
            $def_bd_config['dbsock'] = $sys_mysql_socket;
        }
    }
    $def_bd_section = $amp_conf['AMPDBNAME'];
    $def_ext_config = array('sccpdevice' => "mysql,{$def_bd_section},sccpdeviceconfig",'sccpline' => "mysql,{$def_bd_section},sccpline");

    // Check extconfig file for correct connector values
    $ext_conf = '';
    $ext_conf_file = '';
    $backup_ext = array('_custom.conf', '_additional.conf','.conf');
    foreach ($backup_ext as $value) {
        if (file_exists($dir . '/extconfig' . $value)) {
            // Last possibility is normal file extconfig.conf
            $ext_conf_file = 'extconfig' . $value;
            $ext_conf = $cnf_read->getConfig($ext_conf_file);
            break;
        }
    }
    if (empty($ext_conf_file)) {
        // Have not found a file, so will need to create. $ext_conf must be empty
        $ext_conf_file = 'extconfig.conf';
    }

    if (!empty($ext_conf)) {
        // Have found a file and read a config. Now need to check required settings
        $currentExtSettings = array();
        $writeExtSettings = $ext_conf;
        if (empty($ext_conf['settings']['sccpdevice']) || ($ext_conf['settings']['sccpdevice'] !== $def_ext_config['sccpdevice'])) {
            // Have error in sccpdevice so need to correct
            $writeExtSettings['settings']['sccpdevice'] = $def_ext_config['sccpdevice'];
        }
        if (empty($ext_conf['settings']['sccpline']) || ($ext_conf['settings']['sccpline'] !== $def_ext_config['sccpline'])) {
            // Have error in sccpline so need to correct
            $writeExtSettings['settings']['sccpline'] = $def_ext_config['sccpline'];
        }
        if (!empty($writeExtSettings)) {
            outn("<li>" . _("Updating extconfig file ...  ") . $ext_conf_file . "</li>");
            $cnf_wr->writeConfig($ext_conf_file, $writeExtSettings);
        }
    } else {
        // Either did not find file or file did not contain any config, so create and fill
        outn("<li>" . _("Creating extconfig file ...  ") . $ext_conf_file . "</li>");
        $writeExtSettings['settings']['sccpdevice'] = $def_ext_config['sccpdevice'];
        $writeExtSettings['settings']['sccpline'] = $def_ext_config['sccpline'];
        $cnf_wr->writeConfig($ext_conf_file, $writeExtSettings);
    }

    // Check database settings
    $res_conf = '';
    if (file_exists($dir . '/res_mysql.conf')) {
        $res_conf = $cnf_read->getConfig('res_mysql.conf');
        if (empty($res_conf[$def_bd_section])) {
            $res_conf[$def_bd_section] = $def_bd_config;
            $cnf_wr->writeConfig('res_mysql.conf', $res_conf);
            outn("<li>" . _("Updating res_mysql.conf file ...") . "</li>");
        }
    } elseif (file_exists($dir . '/res_config_mysql.conf')) {
        $res_conf = $cnf_read->getConfig('res_config_mysql.conf');
        if (empty($res_conf[$def_bd_section])) {
            $res_conf[$def_bd_section] = $def_bd_config;
            $cnf_wr->writeConfig('res_config_mysql.conf', $res_conf);
            outn("<li>" . _("Updating res_config_mysql.conf file ...") . "</li>");
        }
    } else {
        // Have not found either res_mysql.conf or res_config_mysql.config
        // So create the latter
        $res_conf[$def_bd_section] = $def_bd_config;
        $cnf_wr->writeConfig('res_config_mysql.conf', $res_conf, false);
    }
}

function addDriver($sccp_compatible) {
    outn("<li>" . _("Adding driver ...") . "</li>");
    $file = "{$_SERVER['DOCUMENT_ROOT']}/admin/modules/core/functions.inc/drivers/Sccp.class.php";
    $contents = "<?php include '/var/www/html/admin/modules/sccp_manager/sccpManClasses/Sccp.class.php.v{$sccp_compatible}'; ?>";
    file_put_contents($file, $contents);

    global $cnf_int;
    $dir = $cnf_int->get('ASTETCDIR');
    if (!file_exists("{$dir}/sccp.conf")) { // System re Config
        outn("<li>" . _("Adding default configuration file ...") . "</li>");
        $sccpfile = file_get_contents("{$_SERVER['DOCUMENT_ROOT']}/admin/modules/sccp_manager/conf/sccp.conf");
        file_put_contents("{$dir}/sccp.conf", $sccpfile);
    }
}
function checkTftpServer() {
    outn("<li>" . _("Checking TFTP server path and availability ...") . "</li>");
    global $db;
    global $cnf_int;
    global $settingsFromDb;
    global $extconfigs;
    global $thisInstaller;
    $confDir = $cnf_int->get('ASTETCDIR');
    $tftpRootPath = "";
    // put the rewrite rules into the required location
    if (file_exists("{$confDir}/sccpManagerRewrite.rules")) {
        rename("{$confDir}/sccpManagerRewrite.rules", "{$confDir}/sccpManagerRewrite.rules.bu");
    }
    copy($_SERVER['DOCUMENT_ROOT'] . '/admin/modules/sccp_manager/conf/mappingRulesHeader',"{$confDir}/sccpManagerRewrite.rules");
    file_put_contents("{$confDir}/sccpManagerRewrite.rules", file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/admin/modules/sccp_manager/contrib/rewrite.rules'), FILE_APPEND);
    file_put_contents("{$confDir}/sccpManagerRewrite.rules", "\n# Do not disable this rule - this is required by sccp_manager\nri ^(.+\.tlzz)?$ settings/\\1", FILE_APPEND);
    // TODO: add option to use external server
    $remoteFileName = ".sccp_manager_installer_probe_sentinel_temp".mt_rand(0, 9999999);
    $remoteFileContent = "# This is a test file created by Sccp_Manager. It can be deleted without impact";
    $possibleFtpDirs = array('/srv', '/srv/tftp','/var/lib/tftp', '/tftpboot');

    // write a couple of sentinels to different distro tftp locations in the filesystem
    // TODO: Depending on distro, do we have write permissions
    foreach ($possibleFtpDirs as $dirToTest) {
        if (is_dir($dirToTest) && is_writable($dirToTest)) {
            $tempFile = "${dirToTest}/{$remoteFileName}";
            file_put_contents($tempFile, $remoteFileContent);

            // try to pull the written file through tftp.
            // this way we can determine if tftp server is active, and what it's
            // source directory is.
            if ($remoteFileContent == $thisInstaller->tftpReadTestFile($remoteFileName)) {
                $tftpRootPath = $dirToTest;
                outn("<li>" . _("Found ftp root dir at {$tftpRootPath}") . "</li>");
                if ($settingsFromDb['tftp_path']['data'] != $tftpRootPath) {
                    $settingsFromDb["tftp_path"] = array( 'keyword' => 'tftp_path', 'seq' => 2, 'type' => 0, 'data' => $tftpRootPath, 'systemdefault' => '');
                }
                // Found sentinel file. Remove it and exit loop
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
                break;
            }
            // Did not find sentinel so remove and continue
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
    if (empty($tftpRootPath)) {
        die_freepbx(_("Either TFTP server is down or TFTP root is non standard. Please fix, refresh, and try again"));
    }

    $settingsFromDb['asterisk_etc_path'] =  array( 'keyword' => 'asterisk_etc_path', 'seq' => 20, 'type' => 0, 'data' => $confDir, 'systemdefault' => '');

    // Get TFTP mapping Status
    $settingsFromDb['tftp_rewrite'] = array('keyword' => 'tftp_rewrite', 'seq' => 20, 'type' => 0, 'data' => 'off', 'systemdefault' => '');
    if (\FreePbx::Sccp_manager()->checkTftpMapping()) {
        $settingsFromDb['tftp_rewrite']['data'] = 'pro';
    }

    // Populate TFTP paths in SccpSettings
    $settingsFromDb = $extconfigs->updateTftpStructure($settingsFromDb);

    foreach ($settingsFromDb as $settingToSave) {
        $sql = "REPLACE INTO sccpsettings (keyword, data, seq, type, systemdefault) VALUES ('{$settingToSave['keyword']}', '{$settingToSave['data']}', {$settingToSave['seq']}, {$settingToSave['type']}, '{$settingToSave['systemdefault']}')";
        $results = $db->query($sql);
        if (DB::IsError($results)) {
            die_freepbx(_("Error updating sccpsettings. $sql"));
        }
    }
    return;
}

function cleanUpSccpSettings() {
    global $thisInstaller;
    global $settingsFromDb;
    global $db;
    global $aminterface;
    global $sccp_compatible;

    // Get current default settings from db
    $stmt = $db->prepare("SELECT * FROM sccpsettings");
    $stmt->execute();
    $settingsFromDb = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    foreach ($settingsFromDb as $key => $rowArray) {
        $settingsFromDb[$rowArray['keyword']] = $rowArray;
        unset($settingsFromDb[$key]);
    }
    // See if a previous version was installed
    outn("<li>" . _("Checking for previous version of Sccp_manager.") . "</li>");
    if (!isset($settingsFromDb['sccp_compatible']['data'])) {
        outn(_("No previous version found "));
    } else {
    outn(_("Found DB Schema : {$settingsFromDb['sccp_compatible']['data']}"));
    }
    // Check that required settings are initialised and update db and $settingsFromDb if not
    /*
    foreach ($extconfigs->getExtConfig('sccpDefaults') as $key => $value) {
        if (empty($settingsFromDb[$key])) {
            $settingsFromDb[$key] = array('keyword' => $key, 'data' => $value, 'type' => 0, 'seq' => 0);

            $sql = "REPLACE INTO sccpsettings (keyword, data, seq, type) VALUES ('{$key}', '{$value}', 0, 0)";
            $results = $db->query($sql);
            if (DB::IsError($results)) {
                die_freepbx(_("Error updating sccpsettings: $key"));
            }
        }
    }
    */

    // Clean up sccpsettings to remove legacy values.
    $xml_vars = "{$_SERVER['DOCUMENT_ROOT']}/admin/modules/sccp_manager/conf/sccpgeneral.xml.v{$sccp_compatible}";
    $thisInstaller->xml_data = simplexml_load_file($xml_vars);
    $thisInstaller->initVarfromXml();
    foreach ( array_diff_key($settingsFromDb,$thisInstaller->sccpvalues) as $key => $valueArray) {
        // Remove legacy values
        unset($settingsFromDb[$key]);
    }
    foreach ($settingsFromDb as $key => $valueArray) {
        $settingsFromDb[$key]['seq'] = $thisInstaller->sccpvalues[$key]['seq'];
        $settingsFromDb[$key]['type'] = $thisInstaller->sccpvalues[$key]['type'];
    }
    $settingsFromDb = array_merge($settingsFromDb, array_diff_key($thisInstaller->sccpvalues, $settingsFromDb));
    unset($thisInstaller->sccpvalues);

    // get chan-sccp defaults

    $sysConfiguration = $aminterface->getSCCPConfigMetaData('general');

    foreach ($sysConfiguration['Options'] as $key => $valueArray) {
        if ($valueArray['Flags'][0] == 'Obsolete' || $valueArray['Flags'][0] == 'Deprecated') {
            continue;
        }

        // 2 special cases deny|permit & disallow|allow where need to parse on |.
        $newKeyword = explode("|", $valueArray['Name'], 2);
        if (isset($newKeyword[1])) {
            $newSysDef = explode("|", $valueArray['DefaultValue'], 2);
            $i = 0;
            foreach ($newKeyword as $dummy) {
                if (array_key_exists($newKeyword[$i],$settingsFromDb)) {
                    if (!empty($newSysDef[$i])) {
                        $settingsFromDb[$newKeyword[$i]]['systemdefault'] = $newSysDef[$i];
                    }
                } else {
                    $settingsFromDb[$newKeyword[$i]] = array('keyword' => $newKeyword[$i], 'seq' => 0, 'type' => 0, 'data' => '', 'systemdefault' => $newSysDef[$i]);
                }
                $i++;
            }
            if (array_key_exists($valueArray['Name'],$settingsFromDb)){
                unset($settingsFromDb[$valueArray['Name']]);
            }
        } else {
            ($sysConfiguration[$valueArray['Name']]['DefaultValue'] == '(null)') ? '' : $sysConfiguration[$valueArray['Name']]['DefaultValue'];
            $sysConfiguration[$valueArray['Name']] = $valueArray;
            if (array_key_exists($valueArray['Name'],$settingsFromDb)) {
                if (!empty($sysConfiguration[$valueArray['Name']]['DefaultValue'])) {
                    // Preserve sequence and type
                    $settingsFromDb[$valueArray['Name']]['systemdefault'] = $sysConfiguration[$valueArray['Name']]['DefaultValue'];
                }
            } else {
                $settingsFromDb[$valueArray['Name']] = array('keyword' => $valueArray['Name'], 'seq' => 0, 'type' => 0, 'data' => '', 'systemdefault' => $sysConfiguration[$valueArray['Name']]['DefaultValue']);
            }
        }
        // Override certain chan-sccp defaults as they are based on a non-FreePbx system
        $settingsFromDb['context']['systemdefault'] = 'from-internal';

        unset($sysConfiguration[$key]);
    }
    unset($sysConfiguration['Options']);

    // Write settings back to db
    $sql = "TRUNCATE sccpsettings";
    $results = $db->query($sql);
    foreach ( $settingsFromDb as $key =>$valueArray ) {
        $sql = "REPLACE INTO sccpsettings
                (keyword, seq, type, data, systemdefault)
                    VALUES
                ( '{$settingsFromDb[$key]['keyword']}',
                  {$settingsFromDb[$key]['seq']},
                  {$settingsFromDb[$key]['type']},
                  '{$settingsFromDb[$key]['data']}',
                  '{$settingsFromDb[$key]['systemdefault']}'
                )";
        $results = $db->query($sql);
    }
}
?>
