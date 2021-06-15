<?php

/**
 *
 */

namespace FreePBX\modules\Sccp_manager;

class extconfigs
{

    public function __construct($parent_class = null)
    {
        $this->paren_class = $parent_class;
    }

    public function info() {
        $Ver = '13.1.1';
        return array('Version' => $Ver,
            'about' => 'Default Setings and Enums ver: ' . $Ver);
    }

    public function getextConfig($id = '', $index = '') {
        switch ($id) {
            case 'keyset':
                $result = $this->keysetdefault;
                break;
            case 'sccp_lang':
                $result = $this->cisco_language;
                break;
            case 'sccpDefaults':
                $result = $this->sccpDefaults;
                break;
            case 'sccp_timezone': // Sccp manager: 1303; server_info: 122
                $result = array();

                if (empty($index)) {
                    return array('offset' => '00', 'daylight' => '', 'cisco_code' => 'Greenwich Standard Time');
                }

                //See if DST is used in this TZ. Test if DST setting is different at
                //various future intervals. If dst changes, this TZ uses dst
                $usesDaylight = false;
                $haveDstNow = date('I');
                $futureDateArray = array(2,4,6,8);
                foreach ($futureDateArray as $numMonths) {
                    $futureDate = (new \DateTime(null,new \DateTimeZone($index)))->modify("+{$numMonths} months");
                    if ($futureDate->format('I') != $haveDstNow) {
                        $usesDaylight = true;
                        break;
                    };
                }
                $thisTzOffset = (new \DateTime(null, new \DateTimeZone($index)))->getOffset();

                // Now look for a match in cisco_tz_array based on offset and DST
                // First correct offset if we have DST now as cisco offsets are
                // based on non dst offsets
                $tmpOffset = $thisTzOffset / 60;
                if ($haveDstNow) {
                    $tmpOffset = $tmpOffset - 60;
                }
                foreach ($this->cisco_timezone as $key => $value) {
                    if (($value['offset'] == $tmpOffset) and ( $value['daylight'] == $usesDaylight )) {
                        // This code may not be the one typically used, but it has the correct values.
                        $cisco_code = $key . ' Standard' . (($usesDaylight) ? '/Daylight' : '') . ' Time';

                        $this->sccpvalues['tzoffset']['data'] = $tmpOffset;

                        return array('offset' => $tmpOffset, 'daylight' => ($usesDaylight) ? 'Daylight' : '', 'cisco_code' => $cisco_code);
                        break;
                    }
                }
                return array('offset' => '00', 'daylight' => '', 'cisco_code' => 'Greenwich Standard Time');

                break;
            default:
                return array('noId');
                break;
        }
        if (empty($index)) {
            return $result;
        } else {
            if (isset($result[$index])) {
                return $result[$index];
            } else {
                return array();
            }
        }
    }

    private function get_cisco_time_zone($tzc)
    {
        $tzdata = $this->cisco_timezone[$tzc];
        $cisco_code = $tzc . ' Standard' . (($tzdata['daylight']) ? '/Daylight' : '') . ' Time';
        return array('offset' => $tzdata['offset'], 'daylight' => $tzdata['daylight'], 'cisco_code' => $cisco_code);
    }

    private $sccpDefaults = array(
        "servername" => 'VPBXSCCP',
        "bindaddr" => '0.0.0.0', "port" => '2000', # chan_sccp also supports ipv6
        "deny" => '0.0.0.0/0.0.0.0',
        "permit" => '0.0.0.0/0.0.0.0', # !TODO!: please change this to 'internal' which would mean:
        # permit:127.0.0.0/255.0.0.0,permit:10.0.0.0/255.0.0.0,permit:172.0.0.0/255.224.0.0,permit:192.168.0.0/255.255.0.0"
        "dateformat" => 'D.M.Y',
        "disallow" => 'all', "allow" => 'alaw;ulaw',
        "hotline_enabled" => 'off',
        "hotline_context" => 'default', # !TODO!: Should this not be from-internal on FreePBX ?
        "hotline_extension" => '*60', # !TODO!: Is this a good default extension to dial for hotline ?
        "hotline_label" => 'hotline',
        "devicetable" => 'sccpdevice',
        "linetable" => 'sccpline',
        "tftp_path" => '/tftpboot'
    );
    private $keysetdefault = array('onhook' => 'redial,newcall,cfwdall,cfwdbusy,cfwdnoanswer,pickup,gpickup,dnd,private',
        'connected' => 'hold,endcall,park,vidmode,select,cfwdall,cfwdbusy,idivert,monitor',
        'onhold' => 'resume,newcall,endcall,transfer,conflist,select,dirtrfr,idivert,meetme',
        'ringin' => 'answer,endcall,transvm,idivert',
        'offhook' => 'redial,endcall,private,cfwdall,cfwdbusy,cfwdnoanswer,pickup,gpickup,meetme,barg',
        'conntrans' => 'hold,endcall,transfer,conf,park,select,dirtrfr,monitor,vidmode,meetme,cfwdal',
        'digitsfoll' => 'back,endcall,dial',
        'connconf' => 'conflist,newcall,endcall,hold,vidmode,monitor',
        'ringout' => 'empty,endcall,transfer',
        'offhookfeat' => 'resume,newcall,endcall',
        'onhint' => 'redial,newcall,pickup,gpickup',
        'onstealable' => 'redial,newcall,barge,intrcpt,cfwdall,pickup,gpickup,dnd',
        'holdconf' => 'resume,newcall,endcall,join',
        'uriaction' => 'default');
//   Cisco  Language Code / Directory
//
    private $cisco_language = array('ar_SA' => array('code' => 'ar', 'language' => 'Arabic', 'locale' => 'Arabic_Saudi_Arabia', 'codepage' => 'ISO8859-1'),
        'bg_BG' => array('code' => 'bg', 'language' => 'Bulgarian', 'locale' => 'Bulgarian_Bulgaria', 'codepage' => 'ISO8859-1'),
        'cz_CZ' => array('code' => 'cz', 'language' => 'Czech', 'locale' => 'Czech_Czech_Republic', 'codepage' => 'ISO8859-1'),
        'da_DK' => array('code' => 'da', 'language' => 'Danish', 'locale' => 'Danish_Denmark', 'codepage' => 'ISO8859-1'),
        'de_DE' => array('code' => 'de', 'language' => 'German', 'locale' => 'German_Germany', 'codepage' => 'ISO8859-1'),
        'el_GR' => array('code' => 'el', 'language' => 'Greek', 'locale' => 'Greek_Greece', 'codepage' => 'ISO8859-1'),
        'en_AU' => array('code' => 'en', 'language' => 'English', 'locale' => 'AU_English_United_States', 'codepage' => 'ISO8859-1'),
        'en_GB' => array('code' => 'en', 'language' => 'English', 'locale' => 'English_United_Kingdom', 'codepage' => 'ISO8859-1'),
        'en_US' => array('code' => 'en', 'language' => 'English', 'locale' => 'English_United_States', 'codepage' => 'ISO8859-1'),
        'es_ES' => array('code' => 'es', 'language' => 'Spanish', 'locale' => 'Spanish_Spain', 'codepage' => 'ISO8859-1'),
        'et_EE' => array('code' => 'et', 'language' => 'Estonian', 'locale' => 'Estonian_Estonia', 'codepage' => 'ISO8859-1'),
        'fi_FI' => array('code' => 'fi', 'language' => 'Finnish', 'locale' => 'Finnish_Finland', 'codepage' => 'ISO8859-1'),
        'fr_CA' => array('code' => 'fr', 'language' => 'French', 'locale' => 'French_Canada', 'codepage' => 'ISO8859-1'),
        'fr_FR' => array('code' => 'fr', 'language' => 'French', 'locale' => 'French_France', 'codepage' => 'ISO8859-1'),
        'he_IL' => array('code' => 'he', 'language' => 'Hebrew', 'locale' => 'Hebrew_Israel', 'codepage' => 'ISO8859-1'),
        'hr_HR' => array('code' => 'hr', 'language' => 'Croatian', 'locale' => 'Croatian_Croatia', 'codepage' => 'ISO8859-1'),
        'hu_HU' => array('code' => 'hu', 'language' => 'Hungarian', 'locale' => 'Hungarian_Hungary', 'codepage' => 'ISO8859-1'),
        'it_IT' => array('code' => 'it', 'language' => 'Italian', 'locale' => 'Italian_Italy', 'codepage' => 'ISO8859-1'),
        'ja_JP' => array('code' => 'ja', 'language' => 'Japanese', 'locale' => 'Japanese_Japan', 'codepage' => 'ISO8859-1'),
        'ko_KO' => array('code' => 'ko', 'language' => 'Korean', 'locale' => 'Korean_Korea_Republic', 'codepage' => 'ISO8859-1'),
        'lt_LT' => array('code' => 'lt', 'language' => 'Lithuanian', 'locale' => 'Lithuanian_Lithuania', 'codepage' => 'ISO8859-1'),
        'lv_LV' => array('code' => 'lv', 'language' => 'Latvian', 'locale' => 'Latvian_Latvia', 'codepage' => 'ISO8859-1'),
        'nl_NL' => array('code' => 'nl', 'language' => 'Dutch', 'locale' => 'Dutch_Netherlands', 'codepage' => 'ISO8859-1'),
        'no_NO' => array('code' => 'no', 'language' => 'Norwegian', 'locale' => 'Norwegian_Norway', 'codepage' => 'ISO8859-1'),
        'pl_PL' => array('code' => 'pl', 'language' => 'Polish', 'locale' => 'Polish_Poland', 'codepage' => 'ISO8859-1'),
        'pt_BR' => array('code' => 'pt', 'language' => 'Portuguese', 'locale' => 'Portuguese_Brazil', 'codepage' => 'ISO8859-1'),
        'pt_PT' => array('code' => 'pt', 'language' => 'Portuguese', 'locale' => 'Portuguese_Portugal', 'codepage' => 'ISO8859-1'),
        'ro_RO' => array('code' => 'ro', 'language' => 'Romanian', 'locale' => 'Romanian_Romania', 'codepage' => 'ISO8859-1'),
        'ru_RU' => array('code' => 'ru', 'language' => 'Russian', 'locale' => 'Russian_Russian_Federation', 'codepage' => 'CP1251'),
        'sk_SK' => array('code' => 'sk', 'language' => 'Slovakian', 'locale' => 'Slovak_Slovakia', 'codepage' => 'ISO8859-1'),
        'sl_SL' => array('code' => 'sl', 'language' => 'Slovenian', 'locale' => 'Slovenian_Slovenia', 'codepage' => 'ISO8859-1'),
        'sr_ME' => array('code' => 'sr', 'language' => 'Serbian', 'locale' => 'Serbian_Republic_of_Montenegro', 'codepage' => 'ISO8859-1'),
        'sr_RS' => array('code' => 'rs', 'language' => 'Serbian', 'locale' => 'Serbian_Republic_of_Serbia', 'codepage' => 'ISO8859-1'),
        'sv_SE' => array('code' => 'sv', 'language' => 'Swedish', 'locale' => 'Swedish_Sweden', 'codepage' => 'ISO8859-1'),
        'th_TH' => array('code' => 'th', 'language' => 'Thailand', 'locale' => 'Thai_Thailand', 'codepage' => 'ISO8859-1'),
        'tr_TR' => array('code' => 'tr', 'language' => 'Turkish', 'locale' => 'Turkish_Turkey', 'codepage' => 'ISO8859-1'),
        'zh_CN' => array('code' => 'cn', 'language' => 'Chinese', 'locale' => 'Chinese_China', 'codepage' => 'ISO8859-1'),
        'zh_TW' => array('code' => 'zh', 'language' => 'Chinese', 'locale' => 'Chinese_Taiwan', 'codepage' => 'ISO8859-1')
    );
    private $cisco_timezone = array(
        'Dateline' => array('offset' => '-720', 'daylight' => false),
        'Samoa' => array('offset' => '-660', 'daylight' => false),
        'Hawaiian' => array('offset' => '-600', 'daylight' => false),
        'Alaskan' => array('offset' => '-540', 'daylight' => true),
        'Pacific' => array('offset' => '-480', 'daylight' => true),
        'Mountain' => array('offset' => '-420', 'daylight' => true),
        'US Mountain' => array('offset' => '-420', 'daylight' => false),
        'Central' => array('offset' => '-360', 'daylight' => true),
        'Mexico' => array('offset' => '-360', 'daylight' => true),
        'Canada Central' => array('offset' => '-360', 'daylight' => false),
        'SA Pacific' => array('offset' => '-300', 'daylight' => false),
        'Eastern' => array('offset' => '-300', 'daylight' => true),
        'US Eastern' => array('offset' => '-300', 'daylight' => false),
        'Atlantic' => array('offset' => '-240', 'daylight' => true),
        'SA Western' => array('offset' => '-240', 'daylight' => false),
        'Pacific SA' => array('offset' => '-240', 'daylight' => false),
        'Newfoundland' => array('offset' => '-210', 'daylight' => true),
        'E. South America' => array('offset' => '-180', 'daylight' => true),
        'SA Eastern' => array('offset' => '-180', 'daylight' => false),
        'Pacific SA' => array('offset' => '-180', 'daylight' => true),
        'Mid-Atlantic' => array('offset' => '-120', 'daylight' => true),
        'Azores' => array('offset' => '-060', 'daylight' => true),
        'GMT' => array('offset' => '00', 'daylight' => true),
        'Greenwich' => array('offset' => '00', 'daylight' => false),
        'W. Europe' => array('offset' => '60', 'daylight' => true),
        'Central Europe' => array('offset' => '120', 'daylight' => true),
        'South Africa' => array('offset' => '120', 'daylight' => false),
        'Saudi Arabia' => array('offset' => '180', 'daylight' => false),
        'Iran' => array('offset' => '210', 'daylight' => true),
        'Caucasus' => array('offset' => '240', 'daylight' => true),
        'Arabian' => array('offset' => '240', 'daylight' => false),
        'Afghanistan' => array('offset' => '270', 'daylight' => false),
        'West Asia' => array('offset' => '300', 'daylight' => false),
        'India' => array('offset' => '330', 'daylight' => false),
        'Central Asia' => array('offset' => '360', 'daylight' => false),
        'SE Asia' => array('offset' => '420', 'daylight' => false),
        'China' => array('offset' => '480', 'daylight' => false),
        'Tokyo' => array('offset' => '540', 'daylight' => false),
        'Cen. Australia' => array('offset' => '570', 'daylight' => true),
        'AUS Central' => array('offset' => '570', 'daylight' => false),
        'E. Australia' => array('offset' => '600', 'daylight' => false),
        'AUS Eastern' => array('offset' => '600', 'daylight' => true),
        'West Pacific' => array('offset' => '600', 'daylight' => false),
        'Tasmania' => array('offset' => '600', 'daylight' => true),
        'Central Pacific' => array('offset' => '660', 'daylight' => false),
        'Fiji' => array('offset' => '720', 'daylight' => false),
        'New Zealand' => array('offset' => '720', 'daylight' => true)
    );

    public function validate_init_path($confDir = '', $db_vars, $sccp_driver_replace = '')
    {
//        global $db;
//        global $amp_conf;
// *** Setings for Provision Sccp
        $adv_config = array('tftproot' => '', 'firmware' => 'firmware', 'settings' => 'settings',
            'locales' => 'locales', 'languages' => 'languages', 'templates' => 'templates', 'dialplan' => 'dialplan', 'softkey' => 'softkey');
// 'pro' /tftpboot - root dir
//       /tftpboot/locales/locales/%Languge_name%
//       /tftpboot/settings/XMLdefault.cnf.xml
//       /tftpboot/settings/SEP[MAC].cnf.xml
//       /tftpboot/firmware/79xx/SCCPxxxx.loads
        $adv_tree['pro'] = array('templates' => 'tftproot', 'settings' => 'tftproot', 'locales' => 'tftproot', 'firmware' => 'tftproot', 'languages' => 'locales', 'dialplan' => 'tftproot', 'softkey' => 'tftproot');

// 'def' /tftpboot - root dir
//       /tftpboot/languages/%Languge_name%
//       /tftpboot/XMLdefault.cnf.xml
//       /tftpboot/SEP[MAC].cnf.xml
//       /tftpboot/SCCPxxxx.loads
        $adv_tree['def'] = array('templates' => 'tftproot', 'settings' => '', 'locales' => '', 'firmware' => '', 'languages' => 'tftproot', 'dialplan' => '', 'softkey' => '');
//        $adv_tree['def']   = Array('templates' => 'tftproot', 'settings' => '', 'locales' => 'tftproot',  'firmware' => 'tftproot', 'languages' => '');
//        $adv_tree['def'] = Array('templates' => 'tftproot', 'settings' => '', 'locales' => 'tftproot', 'firmware' => 'tftproot', 'languages' => 'tftproot');
//* **************------ ****
        $base_tree = array('tftp_templates' => 'templates', 'tftp_path_store' => 'settings', 'tftp_lang_path' => 'languages', 'tftp_firmware_path' => 'firmware', 'tftp_dialplan' => 'dialplan', 'tftp_softkey' => 'softkey');

        if (empty($confDir)) {
            return array('error' => 'empty СonfDir');
        }

        $base_config = array('asterisk' => $confDir, 'sccp_conf' => $confDir . '/sccp.conf', 'tftp_path' => '');

//      Test Base dir (/tftproot)
        if (!empty($db_vars["tftp_path"])) {
            if (file_exists($db_vars["tftp_path"]["data"])) {
                $base_config["tftp_path"] = $db_vars["tftp_path"]["data"];
            }
        }
        if (empty($base_config["tftp_path"])) {
            if (file_exists($this->getextConfig('sccpDefaults', "tftp_path"))) {
                $base_config["tftp_path"] = $this->getextConfig('sccpDefaults', "tftp_path");
            }
        }
        if (empty($base_config["tftp_path"])) {
            if (!empty($this->paren_class)) {
                $this->paren_class->class_error['tftp_path'] = 'Tftp path not exist or not defined';
            }
            return array('error' => 'empty tftp_path');
        }
        if (!is_writeable($base_config["tftp_path"])) {
            if (!empty($this->paren_class)) {
                $this->paren_class->class_error['tftp_path'] = 'No write permission on tftp DIR';
            }
            return array('error' => 'No write permission on tftp DIR');
        }
//      END Test Base dir (/tftproot)

        if (!empty($db_vars['tftp_rewrite_path'])) {
            $adv_ini = $db_vars['tftp_rewrite_path']["data"];
        }

        $adv_tree_mode = 'def';
        if (empty($db_vars["tftp_rewrite"])) {
            $db_vars["tftp_rewrite"]["data"] = "off";
        }

        $adv_config['tftproot'] = $base_config["tftp_path"];
        if ($db_vars["tftp_rewrite"]["data"] == 'pro') {
            $adv_tree_mode = 'pro';
            if (!empty($adv_ini)) { // something found in external conflicts
                $adv_ini .= '/index.cnf';
                if (file_exists($adv_ini)) {
                    $adv_ini_array = parse_ini_file($adv_ini);
                    $adv_config = array_merge($adv_config, $adv_ini_array);
                }
            }
        }
        if ($db_vars["tftp_rewrite"]["data"] == 'on') {
            $adv_tree_mode = 'def';
        }
        foreach ($adv_tree[$adv_tree_mode] as $key => $value) {
            if (!empty($adv_config[$key])) {
                if (!empty($value)) {
                    if (substr($adv_config[$key], 0, 1) != "/") {
                        $adv_config[$key] = $adv_config[$value] . '/' . $adv_config[$key];
                    }
                } else {
                    $adv_config[$key] = $adv_config['tftproot'];
                }
            }
        }
        foreach ($base_tree as $key => $value) {
            $base_config[$key] = $adv_config[$value];
            if (!file_exists($base_config[$key])) {
                if (!mkdir($base_config[$key], 0777, true)) {
                    die('Error creating dir : ' . $base_config[$key]);
                }
            }
        }
        print_r($base_config, 1);
//        die(print_r($base_config,1));
//        $base_config['External_ini'] = $adv_config;
//        $base_config['External_mode'] =  $adv_tree_mode;

        /*
          if (!empty($this->sccppath["tftp_path"])) {
          $this->sccppath["tftp_DP"] = $this->sccppath["tftp_path"] . '/Dialplan';
          if (!file_exists($this->sccppath["tftp_DP"])) {
          if (!mkdir($this->sccppath["tftp_DP"], 0777, true)) {
          die('Error creating DialPlan template dir');
          }
          }
          }
         */
        //    TFTP -REWrite        double model
        if (empty($_SERVER['DOCUMENT_ROOT'])) {
            if (!empty($this->paren_class)) {
                $this->paren_class->class_error['DOCUMENT_ROOT'] = 'Empty DOCUMENT_ROOT';
            }
            $base_config['error'] = 'Empty DOCUMENT_ROOT';
            return $base_config;
        }

        if (!file_exists($base_config["tftp_templates"] . '/XMLDefault.cnf.xml_template')) {
            $src_path = $_SERVER['DOCUMENT_ROOT'] . '/admin/modules/sccp_manager/conf/';
            $dst_path = $base_config["tftp_templates"] . '/';
            foreach (glob($src_path . '*.*_template') as $filename) {
                copy($filename, $dst_path . basename($filename));
            }
        }

        $dst = $_SERVER['DOCUMENT_ROOT'] . '/admin/modules/core/functions.inc/drivers/Sccp.class.php';
        if (!file_exists($dst) || $sccp_driver_replace == 'yes') {
            $src_path = $_SERVER['DOCUMENT_ROOT'] . '/admin/modules/sccp_manager/conf/' . basename($dst) . '.v' . $db_vars['sccp_compatible']['data'];
            if (file_exists($src_path)) {
                copy($src_path, $dst);
            } else {
                // Set new default
                $src_path = $_SERVER['DOCUMENT_ROOT'] . '/admin/modules/sccp_manager/conf/' . basename($dst) . '.v433';
                copy($src_path, $dst);
            }
        }

        if (!file_exists($base_config["sccp_conf"])) { // System re Config
            $sccpfile = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/admin/modules/sccp_manager/conf/sccp.conf');
            file_put_contents($base_config["sccp_conf"], $sccpfile);
        }

        return $base_config;
    }
    // Type declaration in below function is incompatible with PHP 5
    public function validate_RealTime( $connector )
    {
        // This method only checks that asterisk is correctly configured for Realtime
        // It is preventative and does not change anything for Sccp_manager
        global $amp_conf;
        $res = array();
/*        if (empty($connector)) {
            $connector = 'sccp';
        }
        $cnf_int = \FreePBX::Config();
        $cnf_wr = \FreePBX::WriteConfig();
*/
        $cnf_read = \FreePBX::LoadConfig();

        // We are running inside FreePBX so must use the same database
        $def_config = array('sccpdevice' => 'mysql,' . $amp_conf['AMPDBNAME'] . ',sccpdeviceconfig', 'sccpline' => 'mysql,' . $amp_conf['AMPDBNAME'] . ',sccpline');
        $backup_ext = array('_custom.conf', '.conf', '_additional.conf');
        $def_bd_config = array('dbhost' => $amp_conf['AMPDBHOST'], 'dbname' => $amp_conf['AMPDBNAME'],
                              'dbuser' => $amp_conf['AMPDBUSER'], 'dbpass' => $amp_conf['AMPDBPASS'],
                              'dbport' => '3306', 'dbsock' => '/var/lib/mysql/mysql.sock'
                              );
        $dir = $amp_conf['ASTETCDIR'];
        $res_conf_sql = ini_get('pdo_mysql.default_socket');
        $res_conf = '';
        $ext_conf = '';

        foreach ($backup_ext as $fext) {
            if (file_exists($dir . '/extconfig' . $fext)) {
                $ext_conf = $cnf_read->getConfig('extconfig' . $fext);
                if (!empty($ext_conf['settings']['sccpdevice'])) {
                    if ($ext_conf['settings']['sccpdevice'] === $def_config['sccpdevice']) {
                        $res['sccpdevice'] = 'OK';
                        $res['extconfigfile'] = 'extconfig' . $fext;
                    } else {
                        $res['sccpdevice'] .= ' Error in line sccpdevice ';
                    }
                }
                if (!empty($ext_conf['settings']['sccpline'])) {
                    if ($ext_conf['settings']['sccpline'] === $def_config['sccpline']) {
                        $res['sccpline'] = 'OK';
                    } else {
                        $res['sccpline'] .= ' Error in line sccpline ';
                    }
                }
            }
        }

        $res['extconfig'] = 'OK';

        if (empty($res['sccpdevice'])) {
            $res['extconfig'] = ' Option "Sccpdevice" is not configured ';
        }
        if (empty($res['sccpline'])) {
            $res['extconfig'] = ' Option "Sccpline" is not configured ';
        }

        if (empty($res['extconfigfile'])) {
            $res['extconfig'] = 'File extconfig.conf does not exist';
        }

        if (!empty($res_conf_sql)) {
            if (file_exists($res_conf_sql)) {
                $def_bd_config['dbsock'] = $res_conf_sql;
            }
        }
        // Check for mysql config files - should only be one depending on version
        $mySqlConfigFiles = [ 'res_mysql.conf', 'res_config_mysql.conf' ];
        foreach ($mySqlConfigFiles as $sqlConfigFile) {
            if (file_exists( $dir . '/' . $sqlConfigFile )) {
                $res_conf = $cnf_read->getConfig($sqlConfigFile);
                if (empty($res_conf[$connector])) {
                    $res['mysqlconfig'] = 'Config not found in file: ' . $sqlConfigFile;
                } else {
                    if ($res_conf[$connector]['dbsock'] != $def_bd_config['dbsock']) {
                        $res['mysqlconfig'] = 'Mysql Socket Error in file: ' . $sqlConfigFile;
                    }
                }
                if (empty($res['mysqlconfig'])) {
                    $res['mysqlconfig'] = 'OK';
                }
            }
        }

        if (empty($res['mysqlconfig'])) {
            $res['mysqlconfig'] = 'Realtime Error: neither res_config_mysql.conf nor res_mysql.conf found in the path : ' . $dir;
        }
        return $res;
    }
}
