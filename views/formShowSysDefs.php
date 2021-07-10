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
            \FreePbx::sccp_manager()->formcreate->addElementIE($child, $fvalues, $sccp_defaults,$npref);
            break;
        case 'IED':
            \FreePbx::sccp_manager()->formcreate->addElementIED($child, $fvalues, $sccp_defaults,$npref, $napref);
            break;
        case 'IS':
            \FreePbx::sccp_manager()->formcreate->addElementIS($child, $fvalues, $sccp_defaults,$npref);
            break;
        case 'SLD':
        case 'SLM':
        case 'SLK':
        case 'SLP':
            //$this->formcreate->addElementSL($child, $fvalues, $sccp_defaults,$npref);
            //break;
        case 'SLS':
        case 'SLT':
        case 'SLA':
        case 'SLZ':
        case 'SL':
            \FreePbx::sccp_manager()->formcreate->addElementSL2($child, $fvalues, $sccp_defaults,$npref);
            break;
        case 'SDM':
        case 'SDMS':
        case 'SDE':
        case 'SDD':
            \FreePbx::sccp_manager()->formcreate->addElementSD($child, $fvalues, $sccp_defaults,$npref);
            break;
        case 'ITED':
            \FreePbx::sccp_manager()->formcreate->addElementITED($child, $fvalues, $sccp_defaults, $npref, $napref);
            break;
        case 'HLP':
            \FreePbx::sccp_manager()->formcreate->addElementHLP($child, $fvalues, $sccp_defaults,$npref);
            break;
        case 'SLTZN':
            \FreePbx::sccp_manager()->formcreate->addElementSLTZN($child, $fvalues, $sccp_defaults,$npref);
            break;
    }
    echo "<!-- END {$child->label} -->";
}
if ($h_show==1) {
    echo '</div>';
}
?>
