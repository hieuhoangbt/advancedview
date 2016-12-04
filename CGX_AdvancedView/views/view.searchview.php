<?php

if (!defined('sugarEntry') || !sugarEntry)
    die('Not A Valid Entry Point');
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





require_once ('modules/CGX_AdvancedView/views/view.listview.php');
require_once 'modules/CGX_AdvancedView/parsers/constants.php';

class ViewSearchView extends ViewListView
{

    function __construct()
    {
        parent::__construct();
        if (!empty($_REQUEST['searchlayout'])) {
            $this->editLayout = $_REQUEST['searchlayout'];
        }
    }

    /**
     * @see SugarView::_getModuleTitleParams()
     */
    protected function _getModuleTitleParams($browserTitle = false)
    {
        global $mod_strings;

        return array(
            translate('LBL_MODULE_NAME', 'Administration'),
            ModuleBuilderController::getModuleTitle(),
        );
    }

    // DO NOT REMOVE - overrides parent ViewEdit preDisplay() which attempts to load a bean for a non-existent module
    function preDisplay()
    {
        
    }

    function display($preview = false)
    {
        $packageName = (isset($_REQUEST ['view_package'])) ? $_REQUEST ['view_package'] : '';
        require_once 'modules/CGX_AdvancedView/parsers/ParserFactory.php';
        $parser = ParserFactory::getParser($this->editLayout, $this->editModule, $packageName);
        $group_name = (!empty($_REQUEST['group_name'])) ? $_REQUEST['group_name'] : '';
        $bean = new CGX_AdvancedView();
        $bean->retrieve_by_string_fields(array(
            'name' => $this->editModule,
            'group_folder' => $group_name
        ));
        $smarty = parent::constructSmarty($parser, boolval($bean->customer_implementation));
        $smarty->assign('EDIT_DISABLED', boolval($bean->customer_implementation));
        $smarty->assign('group_name', $group_name);
        $smarty->assign('action', 'searchViewSave');
        $smarty->assign('view', $this->editLayout);
        $smarty->assign('helpName', 'searchViewEditor');
        $smarty->assign('helpDefault', 'modify');

        if ($preview) {
            echo $smarty->fetch("modules/CGX_AdvancedView/tpls/Preview/listView.tpl");
        } else {
            $ajax = $this->constructAjax($bean->group_name, $bean->group_folder);
            $ajax->addSection('center', translate($this->title), $smarty->fetch("modules/CGX_AdvancedView/tpls/listView.tpl"));
            echo $ajax->getJavascript();
        }
    }

    function constructAjax($group_name, $group_folder)
    {
        require_once ('modules/CGX_AdvancedView/MB/AjaxCompose.php');
        $ajax = new AjaxCompose ( );
        switch ($this->editLayout) {
            default:
                $searchLabel = 'LBL_' . strtoupper($this->editLayout);
        }

        $layoutLabel = 'LBL_LAYOUTS';
        $layoutView = 'layouts';


        if ($this->fromModuleBuilder) {
            $ajax->addCrumb(translate('LBL_MODULEBUILDER', 'CGX_AdvancedView'), 'ModuleBuilder.main("mb")');
            $ajax->addCrumb($_REQUEST ['view_package'], 'ModuleBuilder.getContent("module=CGX_AdvancedView&action=package&package=' . $_REQUEST ['view_package'] . '")');
            $ajax->addCrumb($this->editModule, 'ModuleBuilder.getContent("module=CGX_AdvancedView&action=module&view_package=' . $_REQUEST ['view_package'] . "&view_module={$this->editModule}" . '")');
            $ajax->addCrumb(translate($layoutLabel, 'ModuleBuilder'), 'ModuleBuilder.getContent("module=CGX_AdvancedView&MB=true&action=wizard&view_module=' . $this->editModule . '&view_package=' . $_REQUEST['view_package'] . '")');
            if ($layoutLabel == 'LBL_LAYOUTS')
                $ajax->addCrumb(translate('LBL_SEARCH_FORMS', 'CGX_AdvancedView'), 'ModuleBuilder.getContent("module=CGX_AdvancedView&MB=true&action=wizard&view=search&view_module=' . $this->editModule . '&view_package=' . $_REQUEST ['view_package'] . '&group_name=' . $group_folder . '")');
            $ajax->addCrumb(translate($searchLabel, 'CGX_AdvancedView'), '');
        } else {
            $ajax->addCrumb(translate('LBL_STUDIO', 'CGX_AdvancedView'), 'ModuleBuilder.main("studio")');
            $ajax->addCrumb($this->translatedEditModule, 'ModuleBuilder.getContent("module=CGX_AdvancedView&action=wizard&view_module=' . $this->editModule . '")');
            $ajax->addCrumb(translate($layoutLabel, 'CGX_AdvancedView'), 'ModuleBuilder.getContent("module=CGX_AdvancedView&action=wizard&view=' . $layoutView . '&view_module=' . $this->editModule . '")');
            if ($layoutLabel == 'LBL_LAYOUTS'){
                $ajax->addCrumb($group_name, 'ModuleBuilder.getContent("module=CGX_AdvancedView&action=wizard&view=groups&view_module=' . $this->editModule . '&group_name=' . $group_folder . '")');
                $ajax->addCrumb(translate('LBL_SEARCH_FORMS', 'CGX_AdvancedView'), 'ModuleBuilder.getContent("module=CGX_AdvancedView&action=wizard&view=search&view_module=' . $this->editModule . '&group_name=' . $group_folder. '")');
            }
            $ajax->addCrumb(translate($searchLabel, 'CGX_AdvancedView'), '');
        }
        $this->title = $searchLabel;
        return $ajax;
    }

}
