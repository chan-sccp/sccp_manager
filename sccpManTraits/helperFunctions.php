<?php

namespace FreePBX\modules\Sccp_manager\sccpManTraits;

trait helperfunctions {

    function getIpInformation($type = '') {
        $interfaces = array();
        switch ($type) {
            case 'ip4':
                exec("/sbin/ip -4 -o addr", $result, $ret);
                break;
            case 'ip6':
                exec("/sbin/ip -6 -o addr", $result, $ret);
                break;

            default:
                exec("/sbin/ip -o addr", $result, $ret);
                break;
        }
        foreach ($result as $line) {
            $vals = preg_split("/\s+/", $line);
            if ($vals[3] == "mtu") {
                continue;
            }
            if ($vals[2] != "inet" && $vals[2] != "inet6") {
                continue;
            }
            if (preg_match("/(.+?)(?:@.+)?:$/", $vals[1], $res)) {
                continue;
            }
            $ret = preg_match("/(\d*+.\d*+.\d*+.\d*+)[\/(\d*+)]*/", $vals[3], $ip);

            $interfaces[$vals[1] . ':' . $vals[2]] = array('name' => $vals[1], 'type' => $vals[2], 'ip' => ((empty($ip[1]) ? '' : $ip[1])));
        }
        return $interfaces;
    }

    private function before($thing, $inthat) {
        return substr($inthat, 0, strpos($inthat, $thing));
    }

    private function array_key_exists_recursive($key, $arr) {
        if (array_key_exists($key, $arr)) {
            return true;
        }
        foreach ($arr as $currentKey => $value) {
            if (is_array($value)) {
                return $this->array_key_exists_recursive($key, $value);
            }
        }
        return false;
    }

    private function strpos_array($haystack, $needles) {
        if (is_array($needles)) {
            foreach ($needles as $str) {
                if (is_array($str)) {
                    $pos = $this->strpos_array($haystack, $str);
                } else {
                    $pos = strpos($haystack, $str);
                }
                if ($pos !== FALSE) {
                    return $pos;
                }
            }
        } else {
            return strpos($haystack, $needles);
        }
        return FALSE;
    }
    private function getTableDefaults($table, $trim_underscore = true) {
        $def_val = array();
        // TODO: This is ugly and overkill - needs to be cleaned up in dbinterface
        if ($table == 'sccpsettings') {
            // sccpsettings has a different structure and already have values in $sccpvalues
            return $this->sccpvalues;
        }
        $sccpTableDesc = $this->dbinterface->getSccpDeviceTableData("get_columns_{$table}");
        foreach ($sccpTableDesc as $data) {
            $key = (string) $data['Field'];
            // function has 2 roles: return actual table keys (trim_underscore = false)
            // return sanitised keys to add defaults (trim_underscore = true)
            if ($trim_underscore) {
                // Remove any leading (or trailing but should be none) underscore
                // These are only used to hide fields from chan-sccp for compatibility
                $key = trim($key,'_');
            }
            $def_val[$key] = array("keyword" => $key, "data" => $data['Default'], "seq" => "99");
        }
        return $def_val;
    }

    private function getTableEnums($table, $trim_underscore = true) {
        $enumFields = array();
        $sccpTableDesc = $this->dbinterface->getSccpDeviceTableData("get_columns_{$table}");

        foreach ($sccpTableDesc as $data) {
            $key = (string) $data['Field'];
            // function has 2 roles: return actual table keys (trim_underscore = false)
            // return sanitised keys to add defaults (trim_underscore = true)
            if ($trim_underscore) {
                // Remove any leading (or trailing but should be none) underscore
                // These are only used to hide fields from chan-sccp for compatibility
                $key = trim($key,'_');
            }
            $typeArray = explode('(', $data['Type']);
            if ($typeArray[0] == 'enum') {
                $enumOptions = explode(',', trim($typeArray[1],')'));
                $enumFields[$key] = $enumOptions;
            }
        }
        return $enumFields;
    }

    private function findAllFiles($dir, $file_mask = null, $mode = 'full') {
        $result = null;
        if (empty($dir) || (!file_exists($dir))) {
            return $result;
        }

        $root = scandir($dir);
        foreach ($root as $value) {
            if ($value === '.' || $value === '..') {
                continue;
            }
            if (is_file("$dir/$value")) {
                $filter = false;
                if (!empty($file_mask)) {
                    if (is_array($file_mask)) {
                        foreach ($file_mask as $k) {
                            if (strpos(strtolower($value), strtolower($k)) !== false) {
                                $filter = true;
                            }
                        }
                    } else {
                        if (strpos(strtolower($value), strtolower($file_mask)) !== false) {
                            $filter = true;
                        }
                    }
                } else {
                    $filter = true;
                }
                if ($filter) {
                    if ($mode == 'fileonly') {
                        $result[] = "$value";
                    } else {
                        $result[] = "$dir/$value";
                    }
                } else {
                    $result[] = null;
                }
                continue;
            }
            $sub_fiend = $this->findAllFiles("$dir/$value", $file_mask, $mode);
            if (!empty($sub_fiend)) {
                foreach ($sub_fiend as $sub_value) {
                    if (!empty($sub_value)) {
                        $result[] = $sub_value;
                    }
                }
            }
        }
        return $result;
    }

    function is_assoc($array) {
        foreach (array_keys($array) as $k => $v) {
            if ($k !== $v)
              return true;
            }
        return false;
    }

    function tftpReadTestFile($remoteFileName, $host = "127.0.0.1")
    {
        // https://datatracker.ietf.org/doc/html/rfc1350
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        // Set timeout so that do not hang if no data received.
        socket_set_option($socket,SOL_SOCKET, SO_RCVTIMEO, array('sec'=>1, 'usec'=>0));

        if ($socket) {
            $port = 69;   // Initial TFTP port. Changed in received packet.

            // create the RRQ request packet
            $packet = chr(0) . chr(1) . $remoteFileName . chr(0) . 'netascii' . chr(0);
            // UDP is connectionless, so we just send it.
            socket_sendto($socket, $packet, strlen($packet), MSG_EOR, $host, $port);

            $buffer = null;
            $port = "";
            $ret = "";
            // MSG_WAITALL is blocking but socket has timeout set to 1 sec.
            $numbytes = socket_recvfrom($socket, $buffer, 84, MSG_WAITALL, $host, $port);

            if ($numbytes < 2) {
                // socket has timed out before data received.
                return false;
            }
            // unpack the returned buffer and discard the first two bytes.
            $pkt = unpack("nopcode/nblockno/a*data", $buffer);

            // send ack and close socket.
            $packet = chr(4) . chr($pkt["blockno"]);
            socket_sendto($socket, $packet, strlen($packet), MSG_EOR, $host, $port);

            socket_close($socket);

            if ($pkt["opcode"] == 3 && $numbytes) {
                return $pkt["data"];
            }
        }
        return false;
    }

    public function initVarfromXml() {
        if ((array) $this->xml_data) {
            foreach ($this->xml_data->xpath('//page_group') as $item) {
                foreach ($item->children() as $child) {
                    $seq = 0;
                    if (!empty($child['seq'])) {
                        $seq = (string) $child['seq'];
                    }
                    if ($seq < 99) {
                        if ($child['type'] == 'IE') {
                            foreach ($child->xpath('input') as $value) {
                                $tp = 0;
                                if (empty($value->value)) {
                                    $datav = (string) $value->default;
                                } else {
                                    $datav = (string) $value->value;
                                }
                                if (strtolower($value->type) == 'number') {
                                    $tp = 1;
                                }
                                if (empty($this->sccpvalues[(string) $value->name])) {
                                    $this->sccpvalues[(string) $value->name] = array('keyword' => (string) $value->name, 'data' => $datav, 'type' => $tp, 'seq' => $seq);
                                }
                            }
                        }
                        if ($child['type'] == 'IS' || $child['type'] == 'IED') {
                            if (empty($child->value)) {
                                $datav = (string) $child->default;
                            } else {
                                $datav = (string) $child->value;
                            }
                            if (empty($this->sccpvalues[(string) $child->name])) {
                                $this->sccpvalues[(string) $child->name] = array('keyword' => (string) $child->name, 'data' => $datav, 'type' => '2', 'seq' => $seq);
                            }
                        }
                        if (in_array($child['type'], array('SLD', 'SLS', 'SLT', 'SL', 'SLM', 'SLZ', 'SLTZN', 'SLA'))) {
                            if (empty($child->value)) {
                                $datav = (string) $child->default;
                            } else {
                                $datav = (string) $child->value;
                            }
                            if (empty($this->sccpvalues[(string) $child->name])) {
                                $this->sccpvalues[(string) $child->name] = array('keyword' => (string) $child->name, 'data' => $datav, 'type' => '2', 'seq' => $seq);
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
