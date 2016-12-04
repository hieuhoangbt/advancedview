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

require_once('modules/CGX_AdvancedView/App/CGX_AdvancedView_DetailView.php');

$originalModule = isset($_REQUEST['original_adv_c']) ? $_REQUEST['original_adv_c'] : '';
if (!empty($originalModule)) {
    /**
     * Check path exists and require
     */
    $customPath = 'custom/modules/' . $originalModule . '/views/view.detail.php';
    $modulePath = 'modules/' . $originalModule . '/views/view.detail.php';
    if (file_exists($customPath)) {
        require_once $customPath;
        $parentClassName = $originalModule . 'ViewDetail';
    } else if (file_exists($modulePath)) {
        require_once $modulePath;
        $parentClassName = $originalModule . 'ViewDetail';
    } else {
        $parentClassName = 'SugarView';
    }
}
eval("class ParentViewDetail extends $parentClassName{}");

/**
 * Default view class for handling DetailViews
 *
 * @package MVC
 * @category Views
 */
class CGX_AdvancedViewViewDetail extends ParentViewDetail
{

    /**
     * @see SugarView::$type
     */
    public $type = 'detail';

    /**
     * @var DetailView2 object
     */
    public $dv;

    /**
     * @var Custom panels
     */
    public $customSectionPanels;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @deprecated deprecated since version 7.6, PHP4 Style Constructors are deprecated and will be remove in 7.8, please update your code, use __construct instead
     */
    function CGX_AdvancedViewViewDetail()
    {
        $deprecatedMessage = 'PHP4 Style Constructors are deprecated and will be remove in 7.8, please update your code';
        if (isset($GLOBALS['log'])) {
            $GLOBALS['log']->deprecated($deprecatedMessage);
        } else {
            trigger_error($deprecatedMessage, E_USER_DEPRECATED);
        }
        self::__construct();
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
        $coreMetaPath = 'modules/' . $module . '/metadata/' . $viewDef . '.php';
        if (file_exists('custom/' . $coreMetaPath)) {
            $metadataFile = 'custom/' . $coreMetaPath;
            $foundViewDefs = true;
        } else {
            if (file_exists('custom/modules/' . $module . '/metadata/metafiles.php')) {
                require_once('custom/modules/' . $module . '/metadata/metafiles.php');
                if (!empty($metafiles[$module][$viewDef])) {
                    $metadataFile = $metafiles[$module][$viewDef];
                    $foundViewDefs = true;
                }
            } elseif (file_exists('modules/' . $module . '/metadata/metafiles.php')) {
                require_once('modules/' . $module . '/metadata/metafiles.php');
                if (!empty($metafiles[$module][$viewDef])) {
                    $metadataFile = $metafiles[$module][$viewDef];
                    $foundViewDefs = true;
                }
            }
        }

        if (!$foundViewDefs && file_exists($coreMetaPath)) {
            $metadataFile = $coreMetaPath;
        }
        $GLOBALS['log']->debug("metadatafile=" . $metadataFile);

        return $metadataFile;
    }

    /**
     * @see SugarView::preDisplay()
     */
    public function preDisplay()
    {
        if (get_parent_class('ParentViewDetail') != 'SugarView') {
            parent::preDisplay();
        }
        global $current_user;
        include_once("modules/ACLRoles/ACLRole.php");
        $roles = ACLRole::getUserRoleNames($current_user->id);
        if (!is_admin($current_user) && !empty($roles)) {
            $groupOfCurrentUser = CGX_AdvancedView::getGroupNameInModuleByRole($_REQUEST['original_adv_c'], $roles);
            if ($groupOfCurrentUser != false) {
                $group_folder = $groupOfCurrentUser['group_folder'];
                $groupMetdata = "custom/modules/{$_REQUEST['original_adv_c']}/metadata/group/$group_folder/detailviewdefs.php";
                $metadataFile = $groupMetdata;
            } else {
                $group_folder = "Default";
                $metadataFile = $this->getMetaDataFile($_REQUEST['original_adv_c']);
            }
        } else {
            $group_folder = "Default";
            $metadataFile = $this->getMetaDataFile($_REQUEST['original_adv_c']);
        }
        require_once $metadataFile;
        $this->dv = new CGX_AdvancedView_DetailView2();
        $this->dv->ss = & $this->ss;
        $this->dv->c_tpl = $this->dv->view.$group_folder;
        $this->dv->setup($_REQUEST['original_adv_c'], $this->bean, $metadataFile, get_custom_file_if_exists('include/DetailView/DetailView.tpl'));
    }

    

    /**
     * @see SugarView::display()
     */
    public function display()
    {
        parent::display();
    }

}
