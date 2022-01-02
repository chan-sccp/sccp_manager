<?php

/**
 *
 */

namespace FreePBX\modules\Sccp_manager;

class sipconfigs
{
//    protected $database;
//    protected $freepbx;

    public function __construct($parent_class = null)
    {
        $this->paren_class = $parent_class;
//        $freepbx
//        $this->database = $freepbx->Database;
    }

    public function info()
    {
        $Ver = '13.0.4';
        return array('Version' => $Ver,
            'about' => 'Sip Setings ver: ' . $Ver);
    }

    public function get_db_sip_TableData($dataid, $data = array())
    {
        global $db;
        if ($dataid == '') {
            return false;
        }
        switch ($dataid) {
            case "Device":
                $sql = "SELECT * FROM sip ORDER BY `id`";
                $tech = array();
                try {
                    $raw_settings = sql($sql, "getAll", DB_FETCHMODE_ASSOC);
                    foreach ($raw_settings as $value) {
                        if (empty($tech[$value['id']]['id'])) {
                            $tech[$value['id']]['id']= $value['id'];
                        }
                        $tech[$value['id']][$value['keyword']]=$value['data'];
                    }
                } catch (\Exception $e) {
                }
                return $tech;
            case "DeviceById":
                $sql = "SELECT keyword,data FROM sip WHERE id = ?";
                $sth = $db->prepare($sql);
                $tech = array();
                try {
                    $id = $data['id'];
                    $sth->execute(array($id));
                    $tech = $sth->fetchAll(\PDO::FETCH_COLUMN | \PDO::FETCH_GROUP);
                    foreach ($tech as &$value) {
                        $value = $value[0];
                    }
                } catch (\Exception $e) {
                }
                return $tech;
        }
    }

    public function getSipConfig()
    {
        // Only called from sccp_manager class when saving SIP device
        $result = array();

        $tmp_binds = \FreePBX::Sipsettings()->getBinds();
        $if_list = $this->paren_class ->getIpInformation('ip4');
        if (!is_array($tmp_binds)) {
            // FreePBX has no sip bindings.
            die_freepbx(_("SIP server configuration error ! No SIP protocols enabled"));
        }
        foreach ($tmp_binds as $fpbx_protocol => $fpbx_bind) {
            foreach ($fpbx_bind as $protocol_ip => $protocol_port_arr) {
                if (empty($protocol_port_arr)) {
                    continue;
                }
                if (($protocol_ip == '0.0.0.0') || ($protocol_ip == '[::]')) {
                    foreach ($if_list as $if_type => $if_data) {
                        if ($if_data['ip'] == "127.0.0.1") {
                            continue;
                        }
                        if (empty($result[$fpbx_protocol][$if_data['ip']])) {
                            $result[$fpbx_protocol][$if_data['ip']]= $protocol_port_arr;
                        } else {
                            $result[$fpbx_protocol][$if_data['ip']]= array_merge($result[$fpbx_protocol][$if_data['ip']],$protocol_port_arr);
                        }
                        $result[$fpbx_protocol][$if_data['ip']]['ip']=$if_data['ip'];
                    }
                } else {
                    $result[$fpbx_protocol][$protocol_ip]=$protocol_port_arr;
                    $result[$fpbx_protocol][$protocol_ip]['ip']=$protocol_ip;
                }
            }
        }
        if (empty($result)) {
            die_freepbx(_("SIP server configuration error ! No SIP protocols enabled"));
        }
        return $result;
    }
}
