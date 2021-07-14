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

    public function checkTftpMapping(){
        exec('in.tftpd -V', $tftpInfo);
        $info['TFTP Server'] = array('Version' => 'Not Found', 'about' => 'Mapping not available');

        if (isset($tftpInfo[0])) {
            $tftpInfo = explode(',',$tftpInfo[0]);
            $info['TFTP Server'] = array('Version' => $tftpInfo[0], 'about' => 'Mapping not available');
            $tftpInfo[1] = trim($tftpInfo[1]);
            $this->sccpvalues['tftp_rewrite']['data'] = 'off';
            if ($tftpInfo[1] == 'with remap') {
                $info['TFTP Server'] = array('Version' => $tftpInfo[0], 'about' => $tftpInfo[1]);

                $remoteFileName = ".sccp_manager_remap_probe_sentinel_temp".mt_rand(0, 9999999).".tlzz";
                $remoteFileContent = "# This is a test file created by Sccp_Manager. It can be deleted without impact";
                $testFtpDir = "{$this->sccpvalues['tftp_path']['data']}/settings";

                // write a sentinel to a tftp subdirectory to see if mapping is working

                if (is_dir($testFtpDir) && is_writable($testFtpDir)) {
                    $tempFile = "${testFtpDir}/{$remoteFileName}";
                    file_put_contents($tempFile, $remoteFileContent);
                    // try to pull the written file through tftp.
                    // this way we can determine if mapping is active and using sccp_manager maps
                    if ($remoteFileContent == $this->tftpReadTestFile($remoteFileName)) {
                        //found the file and contents are correct
                        $this->sccpvalues['tftp_rewrite']['data'] = 'pro';
                    } else {
                        // Did not find sentinel so mapping not available
                        $this->sccpvalues['tftp_rewrite']['data'] = 'off';
                    }
                    unlink($tempFile);
                }
                return true;
            }
        }
        return false;
    }
    // helper function to save xml with proper indentation
    public function saveXml($xml, $filename) {
       $dom = new \DOMDocument("1.0");
       $dom->preserveWhiteSpace = false;
       $dom->formatOutput = true;
       $dom->loadXML($xml->asXML());
       $dom->save($filename);
    }

    public function getFileListFromProvisioner() {

        $provisionerUrl = "https://github.com/dkgroot/provision_sccp/raw/master/";

        // Get master tftpboot directory structure

        file_put_contents("{$this->sccppath['tftp_path']}/masterFilesStructure.xml",file_get_contents("{$provisionerUrl}tools/tftpbootFiles.xml"));
        //$xmlData = simplexml_load_file("{$provisionerUrl}tools/tftpbootFiles.xml");
        return true;

    }

    public function getFilesFromProvisioner($type = "",$name = "",$device = "") {
        $filesToGet = array();
        $provisionerUrl = "https://github.com/dkgroot/provision_sccp/raw/master/";
        if (!$tftpBootXml = simplexml_load_file("{$this->sccppath['tftp_path']}/masterFilesStructure.xml")) {
            $this->getFileListFromProvisioner();
            $tftpBootXml = simplexml_load_file("{$this->sccppath['tftp_path']}/masterFilesStructure.xml");
        }
        switch ($type) {
            case 'firmware':
                if (!is_dir("{$this->sccppath['tftp_path']}/firmware/{$device}")) {
                    mkdir("{$this->sccppath['tftp_path']}/firmware/{$device}", 0755);
                }
                $firmwareDir = $tftpBootXml->xpath("//Directory[@name='firmware']");
                $result = $firmwareDir[0]->xpath("//Directory[@name={$device}]");
                $filesToGet = (array)$result[0]->FileName;
                foreach ($filesToGet as $srcFile) {
                    file_put_contents("{$this->sccppath['tftp_path']}/firmware/{$device}/{$srcFile}",
                        file_get_contents("{$provisionerUrl}" . (string)$result[0]->DirectoryPath . $srcFile));
                }
            return "thanks for trying Diederik :-)";
            break;

          default:
              return false;
              break;
        }
    }
    file_get_contents(https://github.com/dkgroot/provision_sccp/raw/master/tftpboot/firmware/7911/SCCP11.9-4-2SR3-1S.loads): failed to open stream: HTTP request failed! HTTP/1.1 404 Not Found
    File:/var/www/html/admin/modules/sccp_manager/sccpManTraits/helperFunctions.php:291

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
                                    $this->sccpvalues[(string) $value->name] = array('keyword' => (string) $value->name, 'data' => $datav, 'type' => $tp, 'seq' => $seq, 'systemdefault' => '');
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
                                $this->sccpvalues[(string) $child->name] = array('keyword' => (string) $child->name, 'data' => $datav, 'type' => '2', 'seq' => $seq, 'systemdefault' => '');
                            }
                        }
                        if (in_array($child['type'], array('SLD', 'SLS', 'SLT', 'SL', 'SLM', 'SLZ', 'SLTZN', 'SLA'))) {
                            if (empty($child->value)) {
                                $datav = (string) $child->default;
                            } else {
                                $datav = (string) $child->value;
                            }
                            if (empty($this->sccpvalues[(string) $child->name])) {
                                $this->sccpvalues[(string) $child->name] = array('keyword' => (string) $child->name, 'data' => $datav, 'type' => '2', 'seq' => $seq, 'systemdefault' => '');
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
