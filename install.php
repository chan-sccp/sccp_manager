<?php

if (!defined('FREEPBX_IS_AUTH')) {
    die_freepbx('No direct script access allowed');
}

global $db;
global $amp_conf;
global $version;
global $aminterface;
global $mobile_hw;
global $useAmiForSoftKeys;
$mobile_hw = '0';
$autoincrement = (($amp_conf["AMPDBENGINE"] == "sqlite") || ($amp_conf["AMPDBENGINE"] == "sqlite3")) ? "AUTOINCREMENT" : "AUTO_INCREMENT";
$table_req = array('sccpdevice', 'sccpline', 'sccpsettings');
$sccp_compatible = 0;
$chanSCCPWarning = true;
$db_config = '';
$sccp_version = array();

CheckSCCPManagerDBTables($table_req);
CheckAsteriskVersion();

// Have essential tables so can create Sccp_manager object and verify have aminterface
$ss = FreePBX::create()->Sccp_manager;

$class = "\\FreePBX\\Modules\\Sccp_manager\\aminterface";
if (!class_exists($class, false)) {
    include(__DIR__ . "/sccpManClasses/aminterface.class.php");
}
if (class_exists($class, false)) {
    $aminterface = new $class();
}

$sccp_version = CheckChanSCCPCompatible();
$sccp_compatible = $sccp_version[0];
$chanSCCPWarning = $sccp_version[1] ^= 1;
outn("<li>" . _("Sccp model Compatible code : ") . $resultReturned[0] . "</li>");
if ($sccp_compatible == 0) {
    outn("<br>");
    outn("<font color='red'>Chan Sccp not Found. Install it before continuing !</font>");
    die();
}
$db_config   = Get_DB_config($sccp_compatible);
$sccp_db_ver = CheckSCCPManagerDBVersion();

// BackUp Old config
CreateBackUpConfig();
RenameConfig();

InstallDB_updateSchema($db_config);
$stmt = $db->prepare('SELECT CASE WHEN EXISTS(SELECT 1 FROM sccpdevmodel) THEN 0 ELSE 1 END AS IsEmpty;');
$stmt->execute();
$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
if ($result[0]['IsEmpty']) {
    outn("Populating sccpdevmodel...");
    InstallDB_fillsccpdevmodel();
}
if (!$sccp_db_ver) {
    InstallDB_updateSccpDevice();
} else {
    outn("Skip update Device model");
}

InstallDB_createButtonConfigTrigger();
InstallDB_CreateSccpDeviceConfigView($sccp_compatible);
InstallDB_updateDBVer($sccp_compatible);
if ($chanSCCPWarning) {
    outn("<br>");
    outn("<font color='red'>Error: installed version of chan-sccp is not compatible. Please upgrade chan-sccp</font>");
}
Setup_RealTime();
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
            'disallow' => array('drop' => "yes"),
            'callhistory_answered_elsewhere' => array('create' => "enum('Ignore','Missed Calls','Received Calls', 'Placed Calls') NOT NULL default 'Ignore'",
                                                      'modify' => "enum('Ignore','Missed Calls','Received Calls','Placed Calls')"),

            'description' => array('rename' => "_description"),
            'hwlang' => array('rename' => "_hwlang"),
            '_hwlang' => array('create' => 'varchar(12) NULL DEFAULT NULL'),
            '_loginname' => array('create' => 'varchar(20) NULL DEFAULT NULL AFTER `_hwlang`'),
            '_profileid' => array('create' => "INT(11) NOT NULL DEFAULT '0' AFTER `_loginname`"),
            '_dialrules' => array('create' => "VARCHAR(255) NULL DEFAULT NULL AFTER `_profileid`"),

            'useRedialMenu' => array('create' => "VARCHAR(5) NULL DEFAULT 'no' AFTER `_dialrules`"),
            'dtmfmode' => array('drop' => "yes"),
            'force_dtmfmode' => array('create' => "ENUM('auto','rfc2833','skinny') NOT NULL default 'auto'",
                          'modify' => "ENUM('auto','rfc2833','skinny')"),
            'deny' => array('create' => 'VARCHAR(100) NULL DEFAULT NULL', 'modify' => "VARCHAR(100)"),
            'permit' => array('create' => 'VARCHAR(100) NULL DEFAULT NULL', 'modify' => "VARCHAR(100)"),
            'backgroundImage' => array('create' => 'VARCHAR(255) NULL DEFAULT NULL', 'modify' => "VARCHAR(255)"),
            'ringtone' => array('create' => 'VARCHAR(255) NULL DEFAULT NULL', 'modify' => "VARCHAR(255)"),
            'transfer' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
            'cfwdall' => array('create' => "enum('on','off') NOT NULL default 'on'", 'modify' => "enum('on','off')"),
            'cfwdbusy' => array('create' => "enum('on','off') NOT NULL default 'on'", 'modify' => "enum('on','off')"),
            'cfwdnoanswer' => array('create' => "enum('on','off') NOT NULL default 'on'", 'modify' => "enum('on','off')"),
            'park' => array('create' => "enum('on','off') NOT NULL default 'on'", 'modify' => "enum('on','off')"),
            'directrtp' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
            'dndFeature' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
            'earlyrtp' => array('create' => "ENUM('immediate','offHook','dialing','ringout','progress','none') NOT NULL default 'none'",
                                'modify' => "ENUM('immediate','offHook','dialing','ringout','progress','none')"),
            'monitor' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
            'audio_tos' => array('def_modify' => "0xB8"),
            'audio_cos' => array('def_modify' => "6"),
            'video_tos' => array('def_modify' => "0x88"),
            'video_cos' => array('def_modify' => "5"),
            'trustphoneip' => array('drop' => "yes"),
            'transfer_on_hangup' => array('create' => "enum('on','off') NOT NULL DEFAULT 'off'", 'modify' => "enum('on','off')"),
            'phonecodepage' => array('create' => 'VARCHAR(50) NULL DEFAULT NULL', 'modify' => "VARCHAR(50)"),
            'mwilamp' => array('create' => "enum('on','off','wink','flash','blink') NOT NULL  default 'on'",
                              'modify' => "enum('on','off','wink','flash','blink')"),
            'mwioncall' => array('create' => "enum('on','off') NOT NULL default 'on'",
                                'modify' => "enum('on','off')"),
            'private' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
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
            'directed_pickup' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
            'directed_pickup_context' => array('create' => "VARCHAR(100) NULL DEFAULT NULL"),
            'pickup_modeanswer' => array('create' => "enum('on','off') NOT NULL default 'on'", 'modify' => "enum('on','off')"),
            'namedcallgroup' => array('create' => "VARCHAR(100) NULL DEFAULT NULL AFTER `setvar`", 'modify' => "VARCHAR(100)"),
            'namedpickupgroup' => array('create' => "VARCHAR(100) NULL DEFAULT NULL AFTER `namedcallgroup`", 'modify' => "VARCHAR(100)"),
            'adhocNumber' => array('create' => "VARCHAR(45) NULL DEFAULT NULL AFTER `namedpickupgroup`"),
            'meetme' => array('create' => "VARCHAR(5) NULL DEFAULT NULL AFTER `adhocNumber`"),
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
            'transfer' => array('create' => "enum('on','off') NOT NULL default 'on'", 'modify' => "enum('on','off')"),
            'vmnum' => array('def_modify' => "*97"),
            'musicclass' => array('def_modify' => "default"),
            'disallow' => array('create' => "VARCHAR(255) NULL DEFAULT NULL"),
            'allow' => array('create' => "VARCHAR(255) NULL DEFAULT NULL"),
            'id' => array('create' => 'MEDIUMINT(9) NOT NULL AUTO_INCREMENT, ADD UNIQUE(id);', 'modify' => "MEDIUMINT(9)", 'index' => 'id'),
            'echocancel' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
            'silencesuppression' => array('create' => "enum('on','off') NOT NULL default 'off'", 'modify' => "enum('on','off')"),
            'dnd' => array('create' => "enum('off','reject','silent','user') NOT NULL default 'reject'", 'modify' => "enum('off','reject','silent','user')", 'def_modify' => "reject")
        ),
        'sccpuser' => array(
            'name' => array('create' => "varchar(20) NOT NULL", 'modify' => "VARCHAR(20)" ),
            'pin' => array('create' => "varchar(7) NOT NULL", 'modify' => "VARCHAR(7)" ),
            'password' => array('create' => "varchar(7) NOT NULL", 'modify' => "VARCHAR(7)" ),
            'description' => array('create' => "varchar(45) NOT NULL", 'modify' => "VARCHAR(45)" ),
            'roaminglogin' => array('create' => "ENUM('on','off','multi') NOT NULL DEFAULT 'off'", 'modify' => "ENUM('on','off','multi')" ),
            'auto_logout' => array('create' => "ENUM('on','off') NOT NULL DEFAULT 'off'", 'modify' => "ENUM('on','off')" ),
            'homedevice' => array('create' => "varchar(20) NOT NULL", 'modify' => "VARCHAR(20)" ),
            'devicegroup' => array('create' => "varchar(7) NOT NULL", 'modify' => "VARCHAR(7)" ),
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
            'cfwdnoanswer' => array('create' => "enum('on','off') NULL default 'on'", 'modify' => "enum('on','off')"),
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
            'id' => array('create' => "varchar(20) NOT NULL", 'modify' => "VARCHAR(20)" ),
            'name' => array('create' => "varchar(45) NOT NULL", 'modify' => "VARCHAR(45)" ),
        )
    );

    if ($sccp_compatible >= 433) {
        if ($mobile_hw == '1') {
            return $db_config_v4M;
        }
        return $db_config_v4;
    }
}

function CheckSCCPManagerDBTables($table_req)
{
    // These tables should already exist having been created by FreePBX through module.xml
    global $amp_conf;
    global $db;
    outn("<li>" . _("Checking for required Sccp_manager database tables..") . "</li>");
    foreach ($table_req as $value) {
        $check = $db->getRow("SELECT 1 FROM `$value` LIMIT 0", DB_FETCHMODE_ASSOC);
        if (DB::IsError($check)) {
            outn(_("Can't find table: " . $value));
            outn(_("Please goto the chan-sccp/conf directory and create the DB schema manually (See wiki)"));
            die_freepbx("!!!! Installation error: Can not find required " . $value . " table !!!!!!\n");
        }
    }
}

function CheckSCCPManagerDBVersion()
{
    global $db;
    outn("<li>" . _("Checking for previous version of Sccp_manager.") . "</li>");
    $check = $db->getRow("SELECT data FROM `sccpsettings` where keyword ='sccp_compatible'", DB_FETCHMODE_ASSOC);
    if (DB::IsError($check)) {
        outn(_("No previous version found "));
        return false;
    }
    if (!empty($check['data'])) {
        outn(_("Found DB Schema : " . $check['data']));
        return $check['data'];
    } else {
        return false;
    }
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
    outn("<li>" . _("Checking Asterisk Version : ") . $version . "</li>");
    $version = FreePBX::Config()->get('ASTVERSION');
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
                    $sql_modify .= "CHANGE COLUMN  {$row_fld}  {$row_data['rename']} {$row_data['create']}, ";
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
                    $sql_modify .= "MODIFY COLUMN {$row_fld}  SET DEFAULT  {$row_data['def_modify']}, ";
                    $count_modify ++;
                }
                if (!empty($row_data['drop'])) {
                    $sql_create .= "DROP COLUMN {$row_fld}, ";
                    $count_modify ++;
                }
            }
        }
        if (!empty($sql_update)) {
            $sql_update = 'BEGIN; ' . $sql_update . ' COMMIT;';
            sql($sql_update);
            $affected_rows = $db->affectedRows();
            outn("<li>" . _("Updated table rows :") . $affected_rows . "</li>");
        }

        if (!empty($sql_create)) {
            outn("<li>" . _("Adding new FILTER_VALIDATE_INT") . "</li>");
            $sql_create = "ALTER TABLE `" . $tabl_name . "` " . substr($sql_create, 0, -2);
            $check = $db->query($sql_create);
            if (DB::IsError($check)) {
                die_freepbx("Can not create " . $tabl_name . " table sql: " . $sql_create . "n");
            }
        }
        if (!empty($sql_modify)) {
            outn("<li>" . _("Modifying table ") . $tabl_name ."</li>");

            $sql_modify = "ALTER TABLE `" . $tabl_name . "` " . substr($sql_modify, 0, -2) . ';';
            $check = $db->query($sql_modify);
            if (DB::IsError($check)) {
                out("<li>" . print_r($check, 1) . "</li>");
                die("Can not modify " . $tabl_name . " table sql: " . $sql_modify . "n");
                die_freepbx("Can not modify " . $tabl_name . " table sql: " . $sql_modify . "n");
            }
        }
    }
    outn("<li>" . _("Total modify count :") . $count_modify . "</li>");
    return true;
}

function InstallDB_fillsccpdevmodel()
{
    global $db;
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
    return true;
}

function InstallDB_updateSccpDevice()
{
    global $db;
    outn("<li>" . _("Update sccpdevice") . "</li>");
    $sql = "UPDATE `sccpdevice` set audio_tos='0xB8',audio_cos='6',video_tos='0x88',video_cos='5' where audio_tos=NULL or audio_tos='';";
    $check = $db->query($sql);
    if (DB::IsError($check)) {
        die_freepbx("Can not REPLACE defaults into sccpdevice table\n");
    }
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
//    outn("<li>" . $sql . "</li>");
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
    if ($sccp_compatible < 431) {
        $sql = "CREATE OR REPLACE
            ALGORITHM = MERGE
            VIEW sccpdeviceconfig AS
            SELECT GROUP_CONCAT( CONCAT_WS( ',', buttonconfig.type, buttonconfig.name, buttonconfig.options )
            ORDER BY instance ASC
            SEPARATOR ';' ) AS button,
            sccpdevice.type AS type,
            sccpdevice.addon AS addon,
            sccpdevice.description AS description,
            sccpdevice.tzoffset AS tzoffset,
            sccpdevice.transfer AS transfer,
            sccpdevice.cfwdall AS cfwdall,
            sccpdevice.cfwdbusy AS cfwdbusy,
            sccpdevice.imageversion AS imageversion,
            sccpdevice.deny AS deny,
            sccpdevice.permit AS permit,
            sccpdevice.dndFeature AS dndFeature,
            sccpdevice.directrtp AS directrtp,
            sccpdevice.earlyrtp AS earlyrtp,
            sccpdevice.mwilamp AS mwilamp,
            sccpdevice.mwioncall AS mwioncall,
            sccpdevice.pickupexten AS pickupexten,
            sccpdevice.pickupcontext AS pickupcontext,
            sccpdevice.pickupmodeanswer AS pickupmodeanswer,
            sccpdevice.private AS private,
            sccpdevice.privacy AS privacy,
            sccpdevice.nat AS nat,
            sccpdevice.softkeyset AS softkeyset,
            sccpdevice.audio_tos AS audio_tos,
            sccpdevice.audio_cos AS audio_cos,
            sccpdevice.video_tos AS video_tos,
            sccpdevice.video_cos AS video_cos,
            sccpdevice.conf_allow AS conf_allow,
            sccpdevice.conf_play_general_announce AS conf_play_general_announce,
            sccpdevice.conf_play_part_announce AS conf_play_part_announce,
            sccpdevice.conf_mute_on_entry AS conf_mute_on_entry,
            sccpdevice.conf_music_on_hold_class AS conf_music_on_hold_class,
            sccpdevice.conf_show_conflist AS conf_show_conflist,
            sccpdevice.setvar AS setvar,
            sccpdevice.disallow AS disallow,
            sccpdevice.allow AS allow,
            sccpdevice.backgroundImage AS backgroundImage,
            sccpdevice.ringtone AS ringtone,
            sccpdevice.name AS name
            FROM sccpdevice
            LEFT JOIN sccpbuttonconfig buttonconfig ON ( buttonconfig.device = sccpdevice.name )
            GROUP BY sccpdevice.name;";
    } else {
        $sql = "DROP VIEW IF EXISTS sccpdeviceconfig;
                DROP VIEW IF EXISTS sccpuserconfig;";
        ///    global $hw_mobil;

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
    }
    $results = $db->query($sql);
    if (DB::IsError($results)) {
        die_freepbx(sprintf(_("Error updating sccpdeviceconfig view. Command was: %s; error was: %s "), $sql, $results->getMessage()));
    }
    return true;
}
function CreateBackUpConfig()
{
    global $amp_conf;
    outn("<li>" . _("Creating Config BackUp") . "</li>");
    $cnf_int = \FreePBX::Config();
    $backup_files = array('extensions','extconfig','res_mysql', 'res_config_mysql','sccp','sccp_hardware','sccp_extensions');
    $backup_ext = array('_custom.conf', '_additional.conf','.conf');
    $dir = $cnf_int->get('ASTETCDIR');

    $fsql = $dir.'/sccp_backup_'.date("Ymd").'.sql';
    $result = exec('mysqldump '.$amp_conf['AMPDBNAME'].' --password='.$amp_conf['AMPDBPASS'].' --user='.$amp_conf['AMPDBUSER'].' --single-transaction >'.$fsql, $output);

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
    outn("<li>" . _("Create Config BackUp: ") . $filename ."</li>");
}

function RenameConfig()
{
    global $amp_conf;
    outn("<li>" . _("Move Old Config") . "</li>");
    $cnf_int = \FreePBX::Config();
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
    $cnf_int = \FreePBX::Config();
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
    }
    if (file_exists($dir . '/res_config_mysql.conf')) {
        $res_conf = $cnf_read->getConfig('res_config_mysql.conf');
        if (empty($res_conf[$def_bd_section])) {
            $res_conf[$def_bd_section] = $def_bd_config;
            $cnf_wr->writeConfig('res_config_mysql.conf', $res_conf);
            outn("<li>" . _("Updating res_config_mysql.conf file ...") . "</li>");
        }
    }
    if (empty($res_conf)) {
        $res_conf[$def_bd_section] = $def_bd_config;
        $cnf_wr->writeConfig('res_config_mysql.conf', $res_conf, false);
    }
}

?>
