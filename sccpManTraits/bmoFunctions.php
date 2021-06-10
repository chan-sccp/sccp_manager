<?php

namespace FreePBX\modules\Sccp_manager\sccpManTraits;

trait bmoFunctions {

    //Need to reload freePBX for modifications below to work

    //need to catch extensions
    public static function myConfigPageInits() {
        dbug('have config page inits');
        return array("extensions");
    }

    public function doConfigPageInit($page) {
        if ($page == "extensions") {
            dbug('have extensions in doConfigPageInit');
        }
        $this->doGeneralPost();
    }

    // Try to change extensions which is part of core
    public static function myGuiHooks() {
        return array('core');
    }

    public function doGuiHook(&$cc) {
        dbug('$_REQUEST', $_REQUEST);
        //dbug('CC', $cc);
        if ($_REQUEST['display'] == "extensions" ) {
      			if (isset($_REQUEST['tech_hardware']))  {
                dbug('Have caught hook in doGuiHook');
                $this->getActionBar($_REQUEST);
            }
        //$cc->addguielem("_top", new \gui_pageheading('', 'I added a header', false));
        }
    }



    /* unused but FPBX API requires it */

    public function install() {

    }

    /* unused but FPBX API requires it */

    public function uninstall() {

    }

    /* unused but FPBX API requires it */

    public function backup() {

    }

    /* unused but FPBX API requires it */

    public function restore($backup) {

    }

    public function getActionBar($request) {
        $buttons = array();
        switch ($request['display']) {
            case 'sccp_adv':
                if (empty($request['tech_hardware'])) {
                    break;
                }
                $buttons = array(
                    'submit' => array(
                        'name' => 'ajaxsubmit',
                        'id' => 'ajaxsubmit',
                        'value' => _("Save")
                    ),
                    'Save' => array(
                        'name' => 'ajaxsubmit2',
                        'id' => 'ajaxsubmit2',
                        'stayonpage' => 'yes',
                        'value' => _("Save + Continue")
                    ),
                    'cancel' => array(
                        'name' => 'cancel',
                        'id' => 'ajaxcancel',
                        'data-search' => '?display=sccp_adv',
                        'data-hash' => 'sccpdialplan',
                        'value' => _("Cancel")
                    ),
                );
                break;
            case 'sccp_phone':
                if (empty($request['tech_hardware'])) {
                    break;
                }
                $buttons = array(
                    'submit' => array(
                        'name' => 'ajaxsubmit',
                        'id' => 'ajaxsubmit',
                        'value' => _("Save")
                    ),
                    'Save' => array(
                        'name' => 'ajaxsubmit2',
                        'id' => 'ajaxsubmit2',
                        'stayonpage' => 'yes',
                        'value' => _("Save + Continue")
                    ),
                    'cancel' => array(
                        'name' => 'cancel',
                        'id' => 'ajaxcancel',
                        'data-search' => '?display=sccp_phone',
                        'data-hash' => 'sccpdevice',
                        'value' => _("Cancel")
                    ),
                );
                break;
            case 'sccpsettings':
                $buttons = array(
                    'submit' => array(
                        'name' => 'ajaxsubmit',
                        'id' => 'ajaxsubmit',
                        'value' => _("Save")
                    ),
                    'reset' => array(
                        'name' => 'reset',
                        'id' => 'ajaxcancel',
                        'data-reload' => 'reload',
                        'value' => _("Cancel")
                    ),
                );
                break;
                case 'extensions':
                    // only called from configpage inits
                    $buttons = array(
                    'submit' => array(
                        'name' => 'ajaxsubmit',
                        'id' => 'ajaxsubmit',
                        'data-search' => '?display=sccp_custom',
                        'value' => _("Save")
                    ),
                    'Save' => array(
                        'name' => 'ajaxsubmit2',
                        'id' => 'ajaxsubmit2',
                        'stayonpage' => 'yes',
                        'value' => _("Save + Continue")
                    ),
                    'cancel' => array(
                        'name' => 'cancel',
                        'id' => 'ajaxcancel',
                        'data-search' => '?display=sccp_custom',
                        'data-hash' => 'sccpdevice',
                        'value' => _("Cancel")
                    ),
                    );
                break;
        }
        return $buttons;
    }

    public function getRightNav($request) {
        if (isset($request['tech_hardware']) && ($request['tech_hardware'] == 'cisco')) {
            return load_view("/var/www/html/admin/modules/sccp_manager/views/hardware.rnav.php", array('request' => $request));
        }
    }

    public function doGeneralPost() {
        if (!isset($_REQUEST['Submit'])) {
            return;
        }
    }
}
?>
