<?php
/*
 *                          IE - Text Input
 *                         IED - Text Input Dynamic
 *                         ITED- Input Dynamic Table
 *                          IS - Radio box
 *                          SL - Select element
 *                         SLA - Select element (from - data )
 *    Input element Select SLD - Date format
 *                         SLZ - Time Zone
 *                       SLTZN - Time Zone List
 *                         SLT - TFTP Lang
 *                         SLM - Music on hold
 *                         SLK - System KeySet
 *  * Input element Select SLS - System Language
 *    Input element Select SDM - Model List
 *                         SDE - Extension List
 *    Help elemen          HLP - Help Element
 */


// Execution continues at end of file after the class definition. Anonymous class
// instantiated to allow function grouping into discrete methods, and avoid multiple includes.
$thisSccpView = new class{
    public function __construct($parent_class = null) {

    }

    function addElementIE ($child, $fvalues, $sccp_defaults, $npref) {
        $res_input = '';
        $res_name = '';
        $usingSysDefaults = true;
        // if there are multiple inputs, take the first for res_id and shortId
        $shortId = (string)$child->input[0]->name;
        $res_id = $npref.$shortId;
        if (!empty($metainfo[$shortId])) {
            if ($child->meta_help == '1' || $child->help == 'Help!') {
                $child->help = $metainfo[$shortId];
            }
        }

        // --- Add Hidden option
        $res_sec_class ='';
        if (!empty($child ->class)) {
            $res_sec_class = (string)$child ->class;
        }
        if (empty($child->nameseparator)) {
            $child->nameseparator = ' / ';
        }
        $i = 0;
        ?>
        <div class="element-container">
            <div class="row">
                <div class="form-group <?php echo $res_sec_class; ?>">
                    <div class="col-md-3">
                        <label class="control-label" for="<?php echo $res_id; ?>"><?php echo _($child->label);?></label>
                        <i class="fa fa-question-circle fpbx-help-icon" data-for="<?php echo $res_id; ?>"></i>
                    </div>
                    <div class="col-md-3">
        <?php
        // Can have multiple inputs for a field which are displayed with a separator
        foreach ($child->xpath('input') as $value) {
            $res_n =  (string)$value->name;
            $res_name = $npref . $res_n;
            //if (!empty($fvalues[$res_n])) {
            $value->value = $fvalues[$res_n]['data'];
                if (!empty($fvalues[$res_n]['data'])) {
                    if (!empty($sccp_defaults[$res_n]['systemdefault']) && ($sccp_defaults[$res_n]['systemdefault'] != $fvalues[$res_n]['data'])) {
                        $usingSysDefaults = false;
                    }
                }
            //}
            // Default to chan-sccp defaults, not xml defaults.
            //if (empty($value->value)) {
                //$value->value = $sccp_defaults[$res_n]['systemdefault'];
            //}
            if (empty($value->type)) {
                $value->type = 'text';
            }
            if (empty($value->class)) {
                $value->class = 'form-control';
            }
            if ($i > 0) {
                echo $child->nameseparator;
            }
            // Output current value
            if (empty($value->value)) {
                echo "Value not found for {$res_n}";
            }
            echo $value->value;
            $i ++;
        }
        ?>
                    </div>
                    <div class="col-md-4">
                      <span class="radioset">
                        <input type="checkbox"
                            <?php
                            if ($usingSysDefaults) {
                                // Setting a site specific value
                                echo " data-for={$res_id}";
                                echo " class=sccp-edit";
                                echo " id=usedefault_{$res_id}";
                                echo " :checked";
                            } else {
                                // reverting to chan-sccp default values
                                echo " data-for={$res_id}";
                                echo " class=sccp-restore";
                                echo " id=usedefault_{$res_id}";
                                echo " ";
                            }
                            ?>
                        >
                        <label
                            <?php
                            echo "for=usedefault_{$res_id} >";
                            echo ($usingSysDefaults) ? "Customise" : "Use chan-sccp defaults";
                            ?>
                        </label>

                      </span>
                    </div>
                </div>
            </div>
            <div class="row" id="edit_<?php echo $res_id; ?>" style="display: none">
                <div class="form-group <?php echo $res_sec_class; ?>">
                    <div class="col-md-3">
                        <i><?php echo "Enter new site value for {$shortId}"; ?></i>
                    </div>
                    <div class="col-md-9">
                        <?php
                        $i=0;
                        // Can have multiple inputs for a field displayed with a separator
                        foreach ($child->xpath('input') as $value) {
                                $res_n =  (string)$value->name;
                                $res_name = $npref . $res_n;
                            if (empty($res_id)) {
                                $res_id = $res_name;
                            }
                            if (!empty($fvalues[$res_n])) {
                                if (!empty($fvalues[$res_n]['data'])) {
                                    $value->value = $fvalues[$res_n]['data'];
                                }
                            }
                            // Default to chan-sccp defaults, not xml defaults.
                            if (empty($value->value)) {
                                $value->value = $sccp_defaults[$res_n]['systemdefault'];
                            }
                            if (!$usingSysDefaults) {
                                $value->value = $sccp_defaults[$res_n]['systemdefault'];
                            }
                            if (empty($value->type)) {
                                $value->type = 'text';
                            }
                            if (empty($value->class)) {
                                $value->class = 'form-control';
                            }
                            if ($i > 0) {
                                echo $child->nameseparator;
                            }
                            echo '<input type="' . $value->type . '" class="' . $value->class . '" id="' . $res_id . '" name="' . $res_name . '" value="' . $value->value.'"';
                            if (isset($value->options)) {
                                foreach ($value->options ->attributes() as $optkey => $optval) {
                                    echo  ' '.$optkey.'="'.$optval.'"';
                                }
                            }
                            if (!empty($value->min)) {
                                echo  ' min="'.$value->min.'"';
                            }
                            if (!empty($value->max)) {
                                echo  ' max="'.$value->max.'"';
                            }
                            echo  '>';
                            $i ++;
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <span id="<?php echo $res_id;?>-help" class="help-block fpbx-help-block"><?php echo _($child->help);?></span>
                </div>
            </div>
        </div>
        <?php
    }

    function addElementIED($child, $fvalues, $sccp_defaults,$npref, $napref) {
        $res_input = '';
        $res_value = '';
        $opt_at = array();
        $res_n =  (string)$child->name;

        if (!empty($metainfo[$res_n])) {
            if ($child->meta_help == '1' || $child->help == 'Help!') {
                $child->help = $metaInfo[$res_n];
            }
        }
    //        $res_value
        $lnhtm = '';
        $res_id = $napref.$child->name;
        $i = 0;
        $max_row = 255;
        if (!empty($child->max_row)) {
            $max_row = $child->max_row;
        }

        if (!empty($fvalues[$res_n])) {
            if (!empty($fvalues[$res_n]['data'])) {
                $res_value = explode(';', $fvalues[$res_n]['data']);
            }
        }
        if (empty($res_value)) {
            $res_value = array((string) $child->default);
    //            $res_value = explode('/', (string) $child->default);
        }
        ?>
    <div class="element-container">
            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        <div class="form-group">
                            <div class="col-md-3">
                                <label class="control-label" for="<?php echo $res_id; ?>"><?php echo _($child->label);?></label>
                                <i class="fa fa-question-circle fpbx-help-icon" data-for="<?php echo $res_id; ?>"></i>
                            </div>

                            <div class="col-md-9">
                            <?php
                            if (!empty($child->cbutton)) {
                                echo '<div class="form-group form-inline">';
                                foreach ($child->xpath('cbutton') as $value) {
                                    $res_n = $res_id.'[0]['.$value['field'].']';
                                    $res_vf = '';
                                    if ($value['value']=='NONE' && empty($res_value)) {
                                        $res_vf = 'active';
                                    }
                                    $ch_key = array_search($value['value'], $res_value);
                                    if ($ch_key !== false) {
                                        unset($res_value[$ch_key]);
                                        $res_vf = 'active';
                                        $res_value = explode(';', implode(';', $res_value));
                                    }
                                    $opt_hide ='';
                                    $opt_class="button-checkbox";
                                    if (!empty($value->option_hide)) {
                                        $opt_class .= " sccp_button_hide";
                                        $opt_hide = ' data-vhide="'.$value->option_hide.'" data-btn="checkbox" data-clhide="'.$value->option_hide['class'].'" ';
                                    }
                                    if (!empty($child->option_show)) {
                                        if (empty($opt_hide)) {
                                            $opt_hide =' class="sccp_button_hide" ';
                                        }
                                        $opt_hide .= ' data-vshow="'.$child->option_show.'" data-clshow="'.$child->option_show['class'].'" ';
                                    }

                                    if (!empty($value->option_disabled)) {
                                        $opt_class .= " sccp_button_disabled";
                                        $opt_hide = ' data-vhide="'.$value->option_disabled.'" data-btn="checkbox" data-clhide="'.$value->option_disabled['class'].'" ';
                                    }

                                    if (!empty($value->class)) {
                                        $opt_class .= " ".(string)$value->class;
                                    }

                                    echo '<span class="'.$opt_class.'"'.$opt_hide.'><button type="button" class="btn '.$res_vf.'" data-color="primary">';
                                    echo '<i class="state-icon '. (($res_vf == 'active')?'glyphicon glyphicon-check"':'glyphicon glyphicon-uncheck'). '"></i> ';
                                    echo $value.'</button><input type="checkbox" name="'. $res_n.'" class="hidden" '. (($res_vf == 'active')?'checked="checked"':'') .'/></span>';
                                }
                                echo '</div>';
                            }
                            $opt_class = "col-sm-7 ".$res_id."-gr";
                            if (!empty($child->class)) {
                                $opt_class .= " ".(string)$child->class;
                            }
                            echo '<div class = "'.$opt_class.'">';

                            foreach ($res_value as $dat_v) {
                                ?>
                                <div class = "<?php echo $res_id;?> form-group form-inline" data-nextid=<?php echo $i+1;?> >
                                <?php
                                $res_vf = explode('/', $dat_v);
                                $i2 = 0;
                                foreach ($child->xpath('input') as $value) {
                                    $res_n = $res_id.'['.$i.']['.$value['field'].']';
                                    $fields_id = (string)$value['field'];
                                    $opt_at[$fields_id]['nameseparator']=(string)$value['nameseparator'];
                                    if (!empty($value->class)) {
                                        $opt_at[$fields_id]['class']='form-control ' .(string)$value->class;
                                    }
                                    $opt_at[$fields_id]['nameseparator']=(string)$value['nameseparator'];

                                    echo '<input type="text" name="'. $res_n.'" class="'.$opt_at[$fields_id]['class'].'" value="'.$res_vf[$i2].'"';
                                    if (isset($value->options)) {
                                        foreach ($value->options ->attributes() as $optkey => $optval) {
                                            $opt_at[$fields_id]['options'][$optkey]=(string)$optval;
                                            echo  ' '.$optkey.'="'.$optval.'"';
                                        }
                                    }
                                    echo '> '.(string)$value['nameseparator'].' ';
                                    $i2 ++;
                                }
                                if (!empty($child->add_pluss)) {
                                    echo '<button type="button" class="btn btn-primary btn-lg input-js-add" id="'.$res_id.'-btn" data-id="'.$res_id.'" data-for="'.$res_id.'" data-max="'.$max_row.'"data-json="'.bin2hex(json_encode($opt_at)).'"><i class="fa fa-plus pull-right"></i></button>';
                                }
                                echo '</div>';
                                $i++;
                            }
                            ?>
                                </div>
                            <?php
                            if (!empty($child->addbutton)) {
                                echo '<div class = "col-sm-5 '.$res_id.'-gr">';
                                echo '<input type="button" id="'.$res_id.'-btn" data-id="'.$res_id.'" data-for="'.$res_id.'" data-max="'.$max_row.'"data-json="'.bin2hex(json_encode($opt_at)).'" class="input-js-add" value="'._($child->addbutton).'" />';
                                echo '</div>';
                            }
                            ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row"><div class="col-md-12">
                <span id="<?php echo $res_id;?>-help" class="help-block fpbx-help-block"><?php echo _($child->help);?></span>
            </div></div>
    </div>
        <?php
    }

    function addElementIS($child, $fvalues, $sccp_defaults,$npref) {
        $res_n =  (string)$child->name;
        $res_id = $npref.$res_n;
        $res_ext = str_replace($npref,'',$res_n);
        $usingSysDefaults = true;
        if (!empty($metainfo[$res_n])) {
            if ($child->meta_help == '1' || $child->help == 'Help!') {
                $child->help = $metaInfo[$res_n];
            }
        }

        // --- Add Hidden option
        $res_sec_class ='';
        if (!empty($child ->class)) {
            $res_sec_class = (string)$child ->class;
        }
        ?>
        <div class="element-container">
            <div class="row">
                <div class="form-group <?php echo $res_sec_class;?>">
                    <div class="col-md-3 radioset">
                        <label class="control-label" for="<?php echo $res_id; ?>"><?php echo _($child->label)?></label>
                        <i class="fa fa-question-circle fpbx-help-icon" data-for="<?php echo $res_id; ?>"></i>
                    </div>

                    <?php
                    $res_v = '';
                    // set res_v according to precedence Default here, value here, supplied value

                    if (!empty($child->default)) {
                        $res_v = (string)$child->default;
                    }
                    if (!empty($child->value)) {
                         $res_v = (string)$child->value;
                    }
                    if (!empty($fvalues[$res_n])) {
                        if (($fvalues[$res_n]['data'] != '') ) {
                            $res_v = (string)$fvalues[$res_n]['data'];
                        }
                    }
                    if (!empty($sccp_defaults[$res_n]['systemdefault'])) {
                    // There is a system default, so add button to customise or reset
                    // the closing } is after the code to include the button at line ~498

                    //-- Start include of defaults button --
                    echo "<div class=col-md-3>";

                    if (!empty($sccp_defaults[$res_n]['systemdefault']) && ($sccp_defaults[$res_n]['systemdefault'] != $res_v)) {
                        $usingSysDefaults = false;
                    }

                    // Output current value
                    // TODO: This is debug code and needs to be set to only echo res_v
                    echo $res_v . $res_n . $sccp_defaults[$res_n]['systemdefault'] . $usingSysDefaults;
                    ?>
                    </div>
                    <div class="col-md-4">
                      <span class="radioset">
                        <input type="checkbox"
                            <?php
                            if ($usingSysDefaults) {
                                // Setting a site specific value
                                echo " data-for={$res_id}";
                                echo " data-type=radio";
                                echo " class=sccp-edit";
                                echo " id=usedefault_{$res_id}";
                                echo " :checked";
                            } else {
                                // reverting to chan-sccp default values
                                echo " data-for={$res_id}";
                                echo " data-type=radio";
                                echo " class=sccp-restore";
                                echo " id=usedefault_{$res_id}";
                                echo " ";
                            }
                            ?>
                        >
                        <label
                            <?php
                            echo "for=usedefault_{$res_id} >";
                            echo ($usingSysDefaults) ? "Customise" : "Use chan-sccp defaults";
                            ?>
                        </label>
                      </span>
                    </div>
                </div>
            </div>
        <!--    <div class="row" id="edit_<?php echo $res_id; ?>" style="display: none"> -->
            <div class="row" id="edit_<?php echo $res_id; ?>" style="display: none">
                <div class="form-group <?php echo $res_id; ?>">
                    <div class="col-md-3">
                        <i><?php echo "Enter new site value for {$res_n}"; ?></i>
                    </div>
                    <!-- Finish include of defaults button -->
                    <?php
                    // Close the conditional include of the defaults button opened at line ~425
                    }
                    ?>

                    <div class="col-md-9 radioset " data-hide="on">

                      <?php
                        $i = 0;
                        $opt_hide = '';

                        if (!$usingSysDefaults) {
                            $res_v = $sccp_defaults[$res_n]['systemdefault'];
                        }
                        if (!empty($child->option_hide)) {
                            $opt_hide = ' class="sccp_button_hide" data-vhide="'.$child->option_hide.'" data-clhide="'.$child->option_hide['class'].'" ';
                        }
                        if (!empty($child->option_show)) {
                            if (empty($opt_hide)) {
                                $opt_hide =' class="sccp_button_hide" ';
                            }
                            $opt_hide .= ' data-vshow="'.$child->option_show.'" data-clshow="'.$child->option_show['class'].'" ';
                        }
                        foreach ($child->xpath('button') as $value) {
                            $val_check = strtolower((string)$value[@value]);
                            if ($val_check == strtolower($res_v)) {
                                $val_check = " checked";
                            } else {
                                if ($val_check == '' || $val_check == 'none' ) {
                                   if (strtolower($res_v) == 'none' || $res_v == '' )  {
                                      $val_check = " checked";
                                   } else {$val_check = "";}
                                } else {$val_check = "";}
                            }
                            echo "<input type=radio name= {$res_id} id=${res_id}_{$i} value={$value[@value]} {$val_check}{$opt_hide}>";
                            echo "<label for= {$res_id}_{$i}>{$value}</label>";
                            $i++;
                        }
                        ?>
                        </div>
                    </div>
                </div>
            <div class="row"><div class="col-md-12">
                    <span id="<?php echo $res_id;?>-help" class="help-block fpbx-help-block"><?php echo _($child->help);?></span>
            </div></div>
        </div>

        <?php
    }

    function addElementSL($child, $fvalues, $sccp_defaults, $npref) {
        // TODO: Unused function. Integrated into SL2. To be removed after full testing.
        /*
        *    Input element Select SLD - Date format
        *                         SLM - Music on hold
        *                         SLK - System KeySet
        *                         SLP - Dial Paterns
        */
        $res_n =  (string)$child ->name;
        $res_id = $npref.$res_n;
        // $select_opt is a simple array for these types.
        $select_opt = array();

        if (!empty($metainfo[$res_n])) {
            if ($child->meta_help == '1' || $child->help == 'Help!') {
                $child->help = $metaInfo[$res_n];
            }
        }
        if (empty($child->class)) {
            $child->class = 'form-control';
        }
        switch ($child['type']) {
            case 'SLD':
                $day_format = array("D.M.Y", "D.M.YA", "Y.M.D", "YA.M.D", "M-D-Y", "M-D-YA", "D-M-Y", "D-M-YA", "Y-M-D", "YA-M-D", "M/D/Y", "M/D/YA",
                   "D/M/Y", "D/M/YA", "Y/M/D", "YA/M/D", "M/D/Y", "M/D/YA");
                $select_opt= $day_format;
                break;
            case 'SLM':
                if (function_exists('music_list')) {
                    $moh_list = music_list();
                }
                if (!is_array($moh_list)) {
                    $moh_list = array('default');
                }
                $select_opt= $moh_list;
                break;
            case 'SLK':
                $softKeyList = \FreePBX::Sccp_manager()->aminterface->sccp_list_keysets();
                $select_opt= $softKeyList;
                break;
            case 'SLP':
                $dialplan_list = array();
                foreach (\FreePBX::Sccp_manager()->getDialPlanList() as $tmpkey) {
                    $tmp_id = $tmpkey['id'];
                    $dialplan_list[$tmp_id] = $tmp_id;
                }
                $select_opt= $dialplan_list;
                break;
        }
        if (!empty($fvalues[$res_n])) {
            if (!empty($fvalues[$res_n]['data'])) {
                $child->value = $fvalues[$res_n]['data'];
            }
        }
        ?>
        <div class="element-container">
           <div class="row"> <div class="form-group">
                   <div class="col-md-3">
                        <label class="control-label" for="<?php echo $res_id; ?>"><?php echo _($child->label);?></label>
                        <i class="fa fa-question-circle fpbx-help-icon" data-for="<?php echo $res_id; ?>"></i>
                    </div>
                    <div class="col-md-9"><div class = "lnet form-group form-inline" data-nextid=1>
                    <?php
                            echo  '<select name="'.$res_id.'" class="'. $child->class . '" id="' . $res_id . '">';

                    foreach ($select_opt as $key) {
                        echo '<option value="' . $key . '"';
                        if ($key == $child->value) {
                            echo ' selected="selected"';
                        }
                        echo '>' . $key . '</option>';
                    }
                    ?>
                    </select>
                    </div>
                  </div>
                </div>
              </div>
            <div class="row"><div class="col-md-12">
                <span id="<?php echo $res_id;?>-help" class="help-block fpbx-help-block"><?php echo _($child->help);?></span>
            </div>
          </div>
        </div>
        <?php
    }

    function addElementSL2($child, $fvalues, $sccp_defaults,$npref) {
    //       Input element Select SLS - System Language
        $res_n =  (string)$child ->name;
        $res_id = $npref.$res_n;
        $child->value ='';
        // $select_opt is an associative array for these types.
        if (!empty($metainfo[$res_n])) {
            if ($child->meta_help == '1' || $child->help == 'Help!') {
                $child->help = $metaInfo[$res_n];
            }
        }
        switch ($child['type']) {
            case 'SLS':
                if (\FreePBX::Modules()->checkStatus("soundlang")) {
                   $syslangs = \FreePBX::Soundlang()->getLanguages();
                   if (!is_array($syslangs)) {
                       $syslangs = array();
                   }
                }
                $select_opt= $syslangs;
                break;
            case 'SLT':
                $select_opt= $tftp_lang;
                break;
            case 'SLZ':
                $timeZoneOffsetList = array('-12' => 'GMT -12', '-11' => 'GMT -11', '-10' => 'GMT -10', '-09' => 'GMT -9',
                                   '-08' => 'GMT -8',  '-07' => 'GMT -7',  '-06' => 'GMT -6', '-05' => 'GMT -5',
                                   '-04' => 'GMT -4',  '-03' => 'GMT -3',  '-02' => 'GMT -2', '-01' => 'GMT -1',
                                   '00'  => 'GMT', '01' => 'GMT +1',  '02'  => 'GMT +2', '03'  => 'GMT +3',
                                   '04'  => 'GMT +4',   '05' => 'GMT +5',  '06'  => 'GMT +6', '07'  => 'GMT +7',
                                   '08'  => 'GMT +8',   '09' => 'GMT +9',  '10'  => 'GMT +10', '11'=> 'GMT +11', '12' => 'GMT +12');
                $select_opt= $timeZoneOffsetList;
                break;
            case 'SLA':
            $select_opt ='';
                if (!empty($fvalues[$res_n])) {
                    if (!empty($fvalues[$res_n]['data'])) {
                        $res_value = explode(';', $fvalues[$res_n]['data']);
                    }
                    if (empty($res_value)) {
                        $res_value = array((string) $child->default);
                    }
                    foreach ($res_value as $key) {
                        $select_opt[$key]= $key;
                    }
                }
            case 'SLM':
                if (function_exists('music_list')) {
                    $moh_list = music_list();
                }
                if (!is_array($moh_list)) {
                    $moh_list = array('default');
                }
                $select_opt= $moh_list;
                break;
            case 'SLD':
                $day_format = array("D.M.Y", "D.M.YA", "Y.M.D", "YA.M.D", "M-D-Y", "M-D-YA", "D-M-Y", "D-M-YA", "Y-M-D", "YA-M-D", "M/D/Y", "M/D/YA",
                   "D/M/Y", "D/M/YA", "Y/M/D", "YA/M/D", "M/D/Y", "M/D/YA");
                $select_opt= $day_format;
                break;
            case 'SLK':
                $softKeyList = \FreePBX::Sccp_manager()->aminterface->sccp_list_keysets();
                $select_opt= $softKeyList;
                break;
            case 'SLP':
                $dialplan_list = array();
                foreach (\FreePBX::Sccp_manager()->getDialPlanList() as $tmpkey) {
                    $tmp_id = $tmpkey['id'];
                    $dialplan_list[$tmp_id] = $tmp_id;
                }
                $select_opt= $dialplan_list;
                break;
            case 'SL':
                $select_opt = array();
                break;
        }
        if (empty($child->class)) {
            $child->class = 'form-control';
        }
        if (!empty($fvalues[$res_n])) {
            if (!empty($fvalues[$res_n]['data'])) {
                $child->value = $fvalues[$res_n]['data'];
            }
        }
        if (empty($child->value)) {
            if (!empty($child->default)) {
                $child->value = $child->default;
            }
        }
        ?>
        <div class="element-container">
           <div class="row"> <div class="form-group">
                   <div class="col-md-3">
                        <label class="control-label" for="<?php echo $res_id; ?>"><?php echo _($child->label);?></label>
                        <i class="fa fa-question-circle fpbx-help-icon" data-for="<?php echo $res_id; ?>"></i>
                    </div>
                    <div class="col-md-9"><div class = "lnet form-group form-inline" data-nextid=1>
                    <?php
                            echo  '<select name="'.$res_id.'" class="'. $child->class . '" id="' . $res_id . '">';
                    foreach ($select_opt as $key => $val) {
                        if (is_array($val)) {
                            $opt_key = (isset($val['id'])) ? $val['id'] : $key;
                            $opt_val = (isset($val['val'])) ? $val['val'] : $val;
                        } else if (\FreePBX::Sccp_manager()->is_assoc($select_opt)){
                            // have associative array
                            $opt_key = $key;
                            $opt_val = $val;
                        } else {
                            // Have simple array
                            $opt_key = $val;
                            $opt_val = $val;
                        }
                        echo '<option value="' . $opt_key . '"';
                        if ($opt_key == $child->value) {
                            echo ' selected="selected"';
                        }
                        echo '>' . $opt_val. '</option>';
                    }
                    ?> </select>
                    </div>
                  </div>
                </div>
              </div>
            <div class="row"><div class="col-md-12">
                <span id="<?php echo $res_id;?>-help" class="help-block fpbx-help-block"><?php echo _($child->help);?></span>
            </div></div>
        </div>
        <?php
    }

    function addElementSD($child, $fvalues, $sccp_defaults,$npref) {
      /*
      *    Input element Select SDM  - Model List
      *                         SDMS - Sip model List
      *                         SDE  - Extension List
      */
        $res_n =  (string)$child ->name;
        $res_id = $npref.$res_n;

        if (!empty($metainfo[$res_n])) {
            if ($child->meta_help == '1' || $child->help == 'Help!') {
                $child->help = $metaInfo[$res_n];
            }
        }

        if (empty($child->class)) {
            $child->class = 'form-control';
        }
        switch ($child['type']) {
            case 'SDM':
                $model_list = \FreePBX::Sccp_manager()->dbinterface->getSccpDeviceTableData("HWDevice");
                $select_opt= $model_list;
                break;
            case 'SDMS':
                $model_list = \FreePBX::Sccp_manager()->dbinterface->getSccpDeviceTableData("HWSipDevice");
                $select_opt= $model_list;
                break;
            case 'SDE':
                $extension_list = \FreePBX::Sccp_manager()->dbinterface->getSccpDeviceTableData("HWextension");
                $extension_list[]=array( 'model' => 'NONE', 'vendor' => 'CISCO', 'dns' => '0');
                foreach ($extension_list as &$data) {
                    $d_name = explode(';', $data['model']);
                    if (is_array($d_name) && (count($d_name) > 1)) {
                        $data['description'] = count($d_name).'x '.$d_name[0];
                    } else {
                        $data['description'] = $data['model'];
                    }
                }
                unset($data);
                $select_opt= $extension_list;
                break;
            case 'SDD':
                $device_list = \FreePBX::Sccp_manager()->dbinterface->getSccpDeviceTableData("SccpDevice");
                $device_list[]=array('name' => 'NONE', 'description' => 'No Device');
                $select_opt = $device_list;
                break;
        }
        ?>
        <div class="element-container">
           <div class="row"> <div class="form-group">

                   <div class="col-md-3">
                        <label class="control-label" for="<?php echo $res_id; ?>"><?php echo _($child->label);?></label>
                        <i class="fa fa-question-circle fpbx-help-icon" data-for="<?php echo $res_id; ?>"></i>
                    </div>
                    <div class="col-md-9"><div class = "lnet form-group form-inline" data-nextid=1> <?php
                            echo  '<select name="'.$res_id.'" class="'. $child->class . '" id="' . $res_id . '"';
                    if (isset($child->options)) {
                        foreach ($child->options->attributes() as $optkey => $optval) {
                            echo  ' '.$optkey.'="'.$optval.'"';
                        }
                    }
                            echo  '>';

                            $fld  = (string)$child->select['name'];
                            $flv  = (string)$child->select;
                            $flv2 = (string)$child->select['addlabel'];
                            $flk  = (string)$child->select['dataid'];
                            $flkv = (string)$child->select['dataval'];
                            $key  = (string)$child->default;
                    if (!empty($fvalues[$res_n])) {
                        if (!empty($fvalues[$res_n]['data'])) {
                            $child->value = $fvalues[$res_n]['data'];
                            $key = $fvalues[$res_n]['data'];
                        }
                    }

                    foreach ($select_opt as $data) {
                        echo '<option value="' . $data[$fld] . '"';
                        if ($key == $data[$fld]) {
                            echo ' selected="selected"';
                        }
                        if (!empty($flk)) {
                            echo ' data-id="'.$data[$flk].'"';
                        }
                        if (!empty($flkv)) {
                            echo ' data-val="'.$data[$flkv].'"';
                        }
                        echo '>' . $data[$flv];
                        if (!empty($flv2)) {
                            echo ' / '.$data[$flv2];
                        }
                        echo '</option>';
                    }

                    ?>
                    </select>
                    </div>
                  </div>
                </div>
            </div>
            <div class="row"><div class="col-md-12">
                <span id="<?php echo $res_id;?>-help" class="help-block fpbx-help-block"><?php echo _($child->help);?></span>
            </div></div>
        </div>
        <?php
    }

    function addElementITED($child, $fvalues, $sccp_defaults, $npref, $napref) {
        $res_input = '';
        $res_na =  (string)$child->name;

    //        $res_value
        $lnhtm = '';
        $res_id = $napref.$child->name;
        $i = 0;

        if (!empty($fvalues[$res_na])) {
            if (!empty($fvalues[$res_na]['data'])) {
                $res_value = explode(';', $fvalues[$res_na]['data']);
            }
        }
        if (empty($res_value)) {
            $res_value = array((string) $child->default);
        }
        echo "<table class=table table-striped id=dp-table-{$res_id}>";

        foreach ($res_value as $dat_v) {
            echo '<tr data-nextid="'.($i+1).'" class="'.$res_id.'" id="'.$res_id.'-row-'.($i).'"> ';
            if (!empty($child->label)) {
                echo '<td class=""> <div class="input-group">'.$child->label.'</div></td>';
            }

            $res_vf = explode('/', $dat_v);
            $i2 = 0;

            foreach ($child->xpath('element') as $value) {
                $fields_id = (string)strtolower($value['field']);
                $res_n  = $res_id.'['.$i.']['.$fields_id.']';
                $res_ni = $res_id.'_'.$i.'_'.$fields_id;

                $opt_at[$fields_id]['display_prefix']=(string)$value['display_prefix'];
                $opt_at[$fields_id]['display_sufix']=(string)$value['display_sufix'];

                if (empty($value->options->class)) {
                    $opt_at[$fields_id]['options']['class']='form-control';
                }
                $opt_at[$fields_id]['type']=(string)$value['type'];
                $res_opt['addon'] ='';
                if (isset($value->options)) {
                    foreach ($value->options ->attributes() as $optkey => $optval) {
                        $opt_at[$fields_id]['options'][$optkey]=(string)$optval;
                        $res_opt['addon'] .=' '.$optkey.'="'.$optval.'"';
                    }
                }

                echo '<td class="">';
                $res_opt['inp_st'] = '<div class="input-group"> <span class="input-group-addon" id="basep_'.$res_n.'">'.$opt_at[$fields_id]['display_prefix'].'</span>';
                $res_opt['inp_end'] = '<span class="input-group-addon" id="bases_'.$res_n.'">'.$opt_at[$fields_id]['display_sufix'].'</span></div>';
                switch ($value['type']) {
                    case 'date':
                        echo $res_opt['inp_st'].'<input type="date" name="'. $res_n.'" value="'.$res_vf[$i2].'"'.$res_opt['addon']. '>'.$res_opt['inp_end'];
                        break;
                    case 'number':
                        echo $res_opt['inp_st'].'<input type="number" name="'. $res_n.'" value="'.$res_vf[$i2].'"'.$res_opt['addon']. '>'.$res_opt['inp_end'];
                        break;
                    case 'input':
                        echo $res_opt['inp_st'].'<input type="text" name="'. $res_n.'" value="'.$res_vf[$i2].'"'.$res_opt['addon']. '>'.$res_opt['inp_end'];
                        break;
                    case 'title':
                        if ($i > 0) {
                            break;
                        }
                    case 'label':
                        $opt_at[$fields_id]['data'] = (string)$value;
                        echo '<label '.$res_opt['addon'].' >'.(string)$value.'</label>';
                        break;
                    case 'select':
                        echo  $res_opt['inp_st'].'<select name="'.$res_n.'" id="' . $res_n . '"'. $res_opt['addon'].'>';
                        $opt_at[$fields_id]['data']='';
                        foreach ($value->xpath('data') as $optselect) {
                            $opt_at[$fields_id]['data'].= (string)$optselect.';';
                            echo '<option value="' . $optselect. '"';
                            if (strtolower((string)$optselect) == strtolower((string)$res_vf[$i2])) {
                                echo ' selected="selected"';
                            }
                            echo '>' . (string)$optselect. '</option>';
                        }
                        echo  '</select>'.$res_opt['inp_end'];
                        break;
                }
                echo '</td>';
                $i2 ++;
            }
            echo '<td><input type="button" id="'.$res_id.'-btn" data-id="'.($i).'" data-for="'.$res_id.'" data-json="'.bin2hex(json_encode($opt_at)).'" class="table-js-add" value="+" />';
            if ($i > 0) {
                echo '<input type="button" id="'.$res_id.'-btndel" data-id="'.($i).'" data-for="'.$res_id.'" class="table-js-del" value="-" />';
            }

            echo '</td></tr>';
            $i++;
        }
        echo '</table>';
    }

    function addElementHLP($child, $fvalues, $sccp_defaults,$npref) {
        $res_n =  (string)$child ->name;
        $res_id = $npref.$res_n;
        if (empty($child->class)) {
            $child->class = 'form-control';
        }
        ?>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-info-circle"></i> <nbsp> <?php echo _($child->label);?>
                <a data-toggle="collapse" href="<?php echo '#'.$res_id;?>"><i class="fa fa-plus pull-right"></i></a></h3>
            </div>
            <div class="panel-body collapse" id="<?php echo $res_id;?>">
        <?php
        foreach ($child->xpath('element') as $value) {
            switch ($value['type']) {
                case 'p':
                case 'h1':
                case 'h2':
                case 'h3':
                case 'h4':
                    echo '<'.$value['type'].'>'._((string)$value).'</'.$value['type'].'>';
                    break;
                case 'table':
                    echo '<'.$value['type'].' class="table" >';
                    foreach ($value->xpath('row') as $trow) {
                        echo '<tr>';
                        foreach ($trow->xpath('col') as $tcol) {
                            echo '<td>'._((string)$tcol).'</td>';
                        }
                        echo '</tr>';
                    }
                    echo '</'.$value['type'].'>';
                    break;
            }
        }
        ?>
            </div>
        </div>
        <?php
    }

    function addElementSLTZN($child, $fvalues, $sccp_defaults,$npref) {
    //       Input element Select SLTZN - System Time Zone
        $res_n =  (string)$child ->name;
        $res_id = $npref.$res_n;
        $child->value ='';

        if (!empty($metainfo[$res_n])) {
            if ($child->meta_help == '1' || $child->help == 'Help!') {
                $child->help = $metaInfo[$res_n];
            }
        }

        if (empty($child->class)) {
            $child->class = 'form-control';
        }

        if (!empty($fvalues[$res_n])) {
            if (!empty($fvalues[$res_n]['data'])) {
                $child->value = $fvalues[$res_n]['data'];
            }
        }

        $child->value = \date_default_timezone_get();
        ?>
        <div class="element-container">
           <div class="row">
              <div class="form-group">
                  <div class="col-md-3">
                        <label class="control-label" for="<?php echo $res_id; ?>"><?php echo _($child->label);?></label>
                        <i class="fa fa-question-circle fpbx-help-icon" data-for="<?php echo $res_id; ?>"></i>
                  </div>
                  <div class="col-md-9"> <?php
                      echo  $child->value;
                  ?>
                  </div>
                </div>
            </div>
            <div class="row"><div class="col-md-12">
                <span id="<?php echo $res_id;?>-help" class="help-block fpbx-help-block"><?php echo _($child->help);?></span>
            </div></div>
        </div>
        <?php
    }
};
// End of class thisSCCPView ---------------------------------------------------
// Start building page here ------------------------------------------------
// This will not work if the field already has the underscore
$npref = $form_prefix.'_';
$napref = $form_prefix.'-ar_';
if (empty($form_prefix)) {
    $npref = "sccp_";
    $napref ="sccp-ar_";
//} elseif ($form_prefix == 'vendorconfig') {
//    $npref = 'vendorconfig';
//    $napref = 'vendorconfig-ar';
}
if (empty($fvalues)) {
    $fvalues = $sccp_defaults;
}
$items = $itm -> children();

if ($h_show==1) {
    $sec_class ='';
    if (!empty($items ->class)) {
        $sec_class = (string)$items ->class;
    }
    ?>

 <div class="section-title" data-for="<?php echo $npref.$itm['name'];?>">
    <h3><i class="fa fa-minus"></i><?php echo _($items ->label) ?></h3>
 </div>
 <div class="section <?php echo $sec_class;?>" data-id="<?php echo $npref.$itm['name'];?>">

<?php
}

foreach ($items as $child) {
    if (empty($child->help)) {
        $child->help = 'Help is not available.';
        $child->meta_help = '1';
    }
    echo "<!-- Begin {$child->label} -->";
//    $child->meta_help = '1';          // Remove comments to see chan-sccp supplied help !
    switch ($child['type']) {
        case 'IE':
            $thisSccpView->addElementIE($child, $fvalues, $sccp_defaults,$npref);
            break;
        case 'IED':
            $thisSccpView->addElementIED($child, $fvalues, $sccp_defaults,$npref, $napref);
            break;
        case 'IS':
            $thisSccpView->addElementIS($child, $fvalues, $sccp_defaults,$npref);
            break;
        case 'SLD':
        case 'SLM':
        case 'SLK':
        case 'SLP':
            //$thisSccpView->addElementSL($child, $fvalues, $sccp_defaults,$npref);
            //break;
        case 'SLS':
        case 'SLT':
        case 'SLA':
        case 'SLZ':
        case 'SL':
            $thisSccpView->addElementSL2($child, $fvalues, $sccp_defaults,$npref);
            break;
        case 'SDM':
        case 'SDMS':
        case 'SDE':
        case 'SDD':
            $thisSccpView->addElementSD($child, $fvalues, $sccp_defaults,$npref);
            break;
        case 'ITED':
            $thisSccpView->addElementITED($child, $fvalues, $sccp_defaults, $npref, $napref);
            break;
        case 'HLP':
            $thisSccpView->addElementHLP($child, $fvalues, $sccp_defaults,$npref);
            break;
        case 'SLTZN':
            $thisSccpView->addElementSLTZN($child, $fvalues, $sccp_defaults,$npref);
            break;
    }
    echo "<!-- END {$child->label} -->";
}
if ($h_show==1) {
    echo '</div>';
}
?>
