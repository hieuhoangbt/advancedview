<?php

/* * *******************************************************************************
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2013 SugarCRM Inc.

 * SuiteCRM is an extension to SugarCRM Community Edition developed by Salesagility Ltd.
 * Copyright (C) 2011 - 2014 Salesagility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo and "Supercharged by SuiteCRM" logo. If the display of the logos is not
 * reasonably feasible for  technical reasons, the Appropriate Legal Notices must
 * display the words  "Powered by SugarCRM" and "Supercharged by SuiteCRM".
 * ****************************************************************************** */

/*
 * Created on Apr 13, 2007
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
$originalModule = isset($_REQUEST['original_adv_c']) ? $_REQUEST['original_adv_c'] : '';
if (!empty($originalModule)) {
    /**
     * Check path exists and require
     */
    $customPath = 'custom/modules/' . $originalModule . '/views/view.edit.php';
    $modulePath = 'modules/' . $originalModule . '/views/view.edit.php';
    if (file_exists($customPath)) {
        require_once $customPath;
        $parentClassName = $originalModule . 'ViewEdit';
    } else if (file_exists($modulePath)) {
        require_once $modulePath;
        $parentClassName = $originalModule . 'ViewEdit';
    } else {
        $parentClassName = 'SugarView';
    }
}
eval ("class ParentEditView extends $parentClassName{}");
require_once ('modules/CGX_AdvancedView/App/CGX_AdvancedView_EditView.php');

class CustomViewEdit extends ParentEditView {

    var $ev;
    var $type = 'edit';
    var $useForSubpanel = false;  //boolean variable to determine whether view can be used for subpanel creates
    var $useModuleQuickCreateTemplate = false; //boolean variable to determine whether or not SubpanelQuickCreate has a separate display function
    var $showTitle = true;
    /**
     * @var Custom panels
     */
    var $customSectionPanels;

    public function __construct() {
        parent::__construct();
    }

    /**
     * @deprecated deprecated since version 7.6, PHP4 Style Constructors are deprecated and will be remove in 7.8, please update your code, use __construct instead
     */
    public function CustomViewEdit() {
        $deprecatedMessage = 'PHP4 Style Constructors are deprecated and will be remove in 7.8, please update your code';
        if (isset($GLOBALS['log'])) {
            $GLOBALS['log']->deprecated($deprecatedMessage);
        } else {
            trigger_error($deprecatedMessage, E_USER_DEPRECATED);
        }
        self::__construct();
    }

    /**
     * @see SugarView::preDisplay()
     */
    public function preDisplay() {
        if (get_parent_class('ParentEditView') != 'SugarView') {
            parent::preDisplay();
        }
        global $current_user;
        include_once("modules/ACLRoles/ACLRole.php");
        $roles = ACLRole::getUserRoleNames($current_user->id);
        if (!is_admin($current_user) && !empty($roles)) {
            $groupOfCurrentUser = CGX_AdvancedView::getGroupNameInModuleByRole($_REQUEST['original_adv_c'], $roles);
            if ($groupOfCurrentUser != false) {
                $group_folder = $groupOfCurrentUser['group_folder'];
                $groupMetdata = "custom/modules/{$_REQUEST['original_adv_c']}/metadata/group/$group_folder/editviewdefs.php";
                $metadataFile = $groupMetdata;
            } else {
                $group_folder = "Default";
                $metadataFile = $this->getMetaDataFile($_REQUEST['original_adv_c']);
            }
        } else {
            $group_folder = "Default";
            $metadataFile = $this->getMetaDataFile($_REQUEST['original_adv_c']);
        }
        //echo $metadataFile; exit;
        $this->ev = $this->getEditView();
        $this->ev->ss = & $this->ss;
        $this->ev->c_tpl = $this->ev->view.$group_folder;
        $this->ev->setup($_REQUEST['original_adv_c'], $this->bean, $metadataFile, get_custom_file_if_exists('include/EditView/EditView.tpl'));
    }

    function display() {
        // Process here
        parent::display();
    }

    /**
     * Get EditView object
     * @return EditView
     */
    protected function getEditView() {
        return new CGX_AdvancedView_EditView();
    }
    
    /**
     * Return the metadata file that will be used by this view.
     *
     * @return string File location of the metadata file.
     */
    public function getMetaDataFile($module)
    {
        $metadataFile = null;
        $foundViewDefs = false;
        $viewDef = strtolower($this->type) . 'viewdefs';
        $coreMetaPath = 'modules/'.$module.'/metadata/' . $viewDef . '.php';
        if(file_exists('custom/' .$coreMetaPath )){
            $metadataFile = 'custom/' . $coreMetaPath;
            $foundViewDefs = true;
        }else{
            if(file_exists('custom/modules/'.$module.'/metadata/metafiles.php')){
                require_once('custom/modules/'.$module.'/metadata/metafiles.php');
                if(!empty($metafiles[$module][$viewDef])){
                    $metadataFile = $metafiles[$module][$viewDef];
                    $foundViewDefs = true;
                }
            }elseif(file_exists('modules/'.$module.'/metadata/metafiles.php')){
                require_once('modules/'.$module.'/metadata/metafiles.php');
                if(!empty($metafiles[$module][$viewDef])){
                    $metadataFile = $metafiles[$module][$viewDef];
                    $foundViewDefs = true;
                }
            }
        }

        if(!$foundViewDefs && file_exists($coreMetaPath)){
                $metadataFile = $coreMetaPath;
        }
        $GLOBALS['log']->debug("metadatafile=". $metadataFile);

        return $metadataFile;
    }

}
