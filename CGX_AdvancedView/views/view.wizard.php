<?php

/*
 * *******************************************************************************
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2013 SugarCRM Inc.
 *
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
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
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
 * reasonably feasible for technical reasons, the Appropriate Legal Notices must
 * display the words "Powered by SugarCRM" and "Supercharged by SuiteCRM".
 * ******************************************************************************
 */
require_once ('modules/CGX_AdvancedView/MB/CGX_AdvancedViewAjaxCompose.php');
require_once ('modules/CGX_AdvancedView/Module/CGX_AdvancedViewStudioModuleFactory.php');
require_once ('include/MVC/View/SugarView.php');

class ViewWizard extends SugarView
{

    private $view = null;
 // the wizard view to display
    private $actions;

    private $buttons;

    private $question;

    private $title;

    private $help;

    private $editModule;

    private $groupName;

    private $removed;

    function __construct()
    {
        if (isset($_REQUEST['view'])) {
            $this->view = $_REQUEST['view'];
        }

        if (isset($_REQUEST['removed'])) {
            $this->removed = $_REQUEST['removed'];
        }
        $this->groupName = (isset($_REQUEST['group_name'])) ? $_REQUEST['group_name'] : 'Default';
        $this->editModule = (! empty($_REQUEST['view_module'])) ? $_REQUEST['view_module'] : null;
        $this->buttons = array(); // initialize so that modules without subpanels for example don't result in this being unset and causing problems in the smarty->assign
    }

    /**
     *
     * @see SugarView::_getModuleTitleParams()
     */
    protected function _getModuleTitleParams($browserTitle = false)
    {
        global $mod_strings;

        return array(
            translate('LBL_MODULE_NAME', 'Administration'),
            CGX_AdvancedViewController::getModuleTitle()
        );
    }

    function display()
    {
        $this->ajax = new CGX_AdvancedViewAjaxCompose();
        $smarty = new Sugar_Smarty();

        if (isset($_REQUEST['MB'])) {
            $this->processMB($this->ajax);
        } else {
            $this->processStudio($this->ajax);
        }

        $smarty->assign('buttons', $this->buttons);
        $smarty->assign('image_path', $GLOBALS['image_path']);
        $smarty->assign("title", $this->title);
        $smarty->assign("question", $this->question);
        $smarty->assign("defaultHelp", $this->help);
        $smarty->assign("actions", $this->actions);
        if ($this->removed) {
            $smarty->assign("mod", $this->editModule);
            $smarty->assign("removed", $this->removed);
        }

        $this->ajax->addSection('center', $this->title, $smarty->fetch('modules/CGX_AdvancedView/tpls/wizard.tpl'));
        echo $this->ajax->getJavascript();
    }

    function processStudio($ajax)
    {
        $this->ajax->addCrumb(translate('LBL_STUDIO'), 'ModuleBuilder.main("studio")');

        if (! isset($this->editModule)) {
            // Studio Select Module Page
            $this->generateStudioModuleButtons();
            $this->question = translate('LBL_QUESTION_EDIT');
            $this->title = translate('LBL_STUDIO');
            global $current_user;
            if (is_admin($current_user))
                $this->actions = "<input class=\"button\" type=\"button\" id=\"exportBtn\" name=\"exportBtn\" onclick=\"ModuleBuilder.getContent('module=CGX_AdvancedView&action=exportcustomizations');\" value=\"" . translate('LBL_BTN_EXPORT') . '">';

            $this->help = 'studioHelp';
        } else {
            $module = CGX_AdvancedViewStudioModuleFactory::getStudioModule($this->editModule);
            $this->ajax->addCrumb($module->name, ! empty($this->view) ? 'ModuleBuilder.getContent("module=CGX_AdvancedView&action=wizard&view_module=' . $this->editModule . '")' : '');
            switch ($this->view) {
                /**
                 * Overwrite default View
                 * Add groups
                 */
                case 'groups':
                    $this->title = $module->name . " " . translate('LBL_GROUPS');
                    $this->question = translate('LBL_QUESTION_LAYOUTS_IN_GROUP');
                    $this->help = 'groupsHelp';
                    $this->ajax->addCrumb(translate('LBL_LAYOUTS'), 'ModuleBuilder.getContent("module=CGX_AdvancedView&action=wizard&view=layouts&view_module=' . $this->editModule . '&group_name=' . $this->groupName . '")');
                    $this->ajax->addCrumb($this->groupName, '');
                    $this->buttons = $module->getLayouts($this->groupName);
                    if ($this->groupName != 'Default') {
                        $this->actions = "<input class=\"button\" type=\"button\" id=\"UpdateGroup\" name=\"UpdateGroup\" " . "onclick=\"ModuleBuilder.getContent('module=CGX_AdvancedView&action=makegroup&module_setting={$this->editModule}&group_name={$this->groupName}');\" value=\"" . translate('LBL_UPDATE_GROUP') . '">';
                        $this->actions .= "<td style='width:20px'> </td>";
                        $this->actions .= "<input class=\"button\" type=\"button\" id=\"delGroup\" name=\"delGroup\" " . "onclick=\"ModuleBuilder.getContent('module=CGX_AdvancedView&action=deletegroup&module_setting={$this->editModule}&group_name={$this->groupName}');\" value=\"" . translate('LBL_DEL_GROUP') . '">';
                        $this->actions .= "<input class=\"button\" type=\"button\" id=\"cloneThisGroup\" name=\"cloneThisGroup\" " . "onclick=\"ModuleBuilder.getContent('module=CGX_AdvancedView&action=makegroup&module_setting={$this->editModule}&group_name={$this->groupName}&clone_group=1');\" value=\"" . translate('LBL_CLONE_GROUP') . '">';
                    }
                    break;
                case 'layouts':
                    // Studio Select Layout page
                    $this->buttons = $module->genGroup();
                    $this->title = $module->name . " " . translate('LBL_LAYOUTS');
                    $this->question = translate('LBL_QUESTION_GROUP');
                    $this->help = 'layoutsHelp';
                    $this->ajax->addCrumb(translate('LBL_LAYOUTS'), '');
                    $this->actions = "<input class=\"button\" type=\"button\" id=\"cloneGroup\" name=\"cloneGroup\" " . "onclick=\"ModuleBuilder.getContent('module=CGX_AdvancedView&action=makegroup&module_setting=$this->editModule');\" value=\"" . translate('LBL_NEW_GROUP') . '">';
                    break;
                case 'search':
                    // Studio Select Search Layout page.
                    $this->buttons = $module->getSearch($this->groupName);
                    $this->title = $module->name . " " . translate('LBL_SEARCH');
                    $this->question = translate('LBL_QUESTION_SEARCH');
                    $this->ajax->addCrumb(translate('LBL_LAYOUTS'), 'ModuleBuilder.getContent("module=CGX_AdvancedView&action=wizard&view=layouts&view_module=' . $this->editModule .'")');
                    $this->ajax->addCrumb($this->groupName, 'ModuleBuilder.getContent("module=CGX_AdvancedView&action=wizard&view=groups&view_module=' . $this->editModule . '&group_name=' . $this->groupName . '")');
                    $this->ajax->addCrumb(translate('LBL_SEARCH'), '');
                    $this->help = 'searchHelp';
                    break;
                case 'dashlet':
                    $this->generateStudioDashletButtons($this->groupName);
                    $this->title = $this->editModule . " " . translate('LBL_DASHLET');
                    $this->question = translate('LBL_QUESTION_DASHLET');
                    $this->ajax->addCrumb(translate('LBL_LAYOUTS'), 'ModuleBuilder.getContent("module=CGX_AdvancedView&action=wizard&view=layouts&view_module=' . $this->editModule . '")');
                    $this->ajax->addCrumb($this->groupName, 'ModuleBuilder.getContent("module=CGX_AdvancedView&action=wizard&view=groups&view_module=' . $this->editModule . '&group_name=' . $this->groupName . '")');
                    $this->ajax->addCrumb(translate('LBL_DASHLET'), '');
                    $this->help = 'dashletHelp';
                    break;

                case 'popup':
                    $this->generateStudioPopupButtons($this->groupName);
                    $this->title = $this->editModule . " " . translate('LBL_POPUP');
                    $this->question = translate('LBL_QUESTION_POPUP');
                    $this->ajax->addCrumb(translate('LBL_LAYOUTS'), 'ModuleBuilder.getContent("module=CGX_AdvancedView&action=wizard&view=layouts&view_module=' . $this->editModule . '")');
                    $this->ajax->addCrumb($this->groupName, 'ModuleBuilder.getContent("module=CGX_AdvancedView&action=wizard&view=groups&view_module=' . $this->editModule . '&group_name=' . $this->groupName . '")');
                    $this->ajax->addCrumb(translate('LBL_POPUP'), '');
                    $this->help = 'popupHelp';
                    break;
                default:
                    // Studio Edit Module Page
                    $this->buttons = $module->getModule();
                    $this->question = translate('LBL_QUESTION_MODULE');
                    $this->title = translate('LBL_EDIT') . " " . $module->name;
                    $this->help = 'moduleHelp';
            }
        }
    }

    function processMB($ajax)
    {
        if (! isset($_REQUEST['view_package'])) {
            sugar_die("no ModuleBuilder package set");
        }

        $this->editModule = $_REQUEST['view_module'];
        $this->package = $_REQUEST['view_package'];

        $ajax->addCrumb(translate('LBL_MODULEBUILDER', 'ModuleBuilder'), 'ModuleBuilder.main("mb")');
        $ajax->addCrumb($this->package, 'ModuleBuilder.getContent("module=CGX_AdvancedView&action=package&view_package=' . $this->package . '")');
        $ajax->addCrumb($this->editModule, 'ModuleBuilder.getContent("module=CGX_AdvancedView&action=module&view_module=' . $this->editModule . '&view_package=' . $this->package . '")');

        switch ($this->view) {
            case 'search':
                // MB Select Search Layout page.
                $this->generateMBSearchButtons();
                $this->title = $this->editModule . " " . translate('LBL_SEARCH');
                $this->question = translate('LBL_QUESTION_SEARCH');
                $ajax->addCrumb(translate('LBL_LAYOUTS'), 'ModuleBuilder.getContent("module=CGX_AdvancedView&MB=true&action=wizard&view_module=' . $this->editModule . '&view_package=' . $this->package . '")');
                $ajax->addCrumb(translate('LBL_SEARCH_FORMS'), '');
                $this->help = "searchHelp";
                break;

            case 'subpanel':
                // ModuleBuilder Select Subpanel
                $ajax->addCrumb($this->editModule, 'ModuleBuilder.getContent("module=CGX_AdvancedView&action=module&view_module=' . $this->editModule . '&view_package=' . $this->package . '")');
                $ajax->addCrumb(translate('LBL_SUBPANELS'), '');
                $this->question = translate('LBL_QUESTION_SUBPANEL');
                $this->help = 'subpanelHelp';
                break;

            case 'dashlet':
                $this->generateMBDashletButtons($this->groupName);
                $this->title = $this->editModule . " " . translate('LBL_DASHLET');
                $this->question = translate('LBL_QUESTION_DASHLET');
                $this->ajax->addCrumb(translate('LBL_LAYOUTS'), 'ModuleBuilder.getContent("module=CGX_AdvancedView&action=wizard&view=layouts&MB=1&view_package=' . $this->package . '&view_module=' . $this->editModule . '")');
                $this->ajax->addCrumb(translate('LBL_DASHLET'), '');
                $this->help = 'dashletHelp';
                break;

            case 'popup':
                $this->generateMBPopupButtons($this->groupName);
                $this->title = $this->editModule . " " . translate('LBL_POPUP');
                $this->question = translate('LBL_QUESTION_POPUP');
                $this->ajax->addCrumb(translate('LBL_LAYOUTS'), 'ModuleBuilder.getContent("module=CGX_AdvancedView&MB=true&action=wizard&view=layouts&MB=1&view_package=' . $this->package . '&view_module=' . $this->editModule . '")');
                $this->ajax->addCrumb(translate('LBL_POPUP'), '');
                $this->help = 'popupHelp';
                break;
            default:
                $ajax->addCrumb(translate('LBL_LAYOUTS'), '');
                $this->generateMBViewButtons();
                $this->title = $this->editModule . " " . translate('LBL_LAYOUTS');
                $this->question = translate('LBL_QUESTION_LAYOUT');
                $this->help = "layoutsHelp";
        }
    }

    function generateStudioModuleButtons()
    {
        require_once ('modules/CGX_AdvancedView/Module/CGX_AdvancedViewStudioBrowser.php');
        $sb = new CGX_AdvancedViewStudioBrowser();
        $sb->loadModules();
        $nodes = $sb->getNodes();
        $this->buttons = array();
        // $GLOBALS['log']->debug(print_r($nodes,true));
        foreach ($nodes as $module) {
            $this->buttons[$module['name']] = array(
                'action' => $module['action'],
                'imageTitle' => ucfirst($module['module'] . "_32"),
                'size' => '32',
                'linkId' => 'studiolink_' . $module['module']
            );
        }
    }

    function generateMBViewButtons()
    {
        $this->buttons[$GLOBALS['mod_strings']['LBL_EDITVIEW']] = array(
            'action' => "module=CGX_AdvancedView&MB=true&action=editLayout&view=" . MB_EDITVIEW . "&view_module={$this->editModule}&view_package={$this->package}",
            'imageTitle' => 'EditView',
            'help' => 'viewBtnEditView'
        );
        $this->buttons[$GLOBALS['mod_strings']['LBL_DETAILVIEW']] = array(
            'action' => "module=CGX_AdvancedView&MB=true&action=editLayout&view=" . MB_DETAILVIEW . "&view_module={$this->editModule}&view_package={$this->package}",
            'imageTitle' => 'DetailView',
            'help' => 'viewBtnListView'
        );
        $this->buttons[$GLOBALS['mod_strings']['LBL_LISTVIEW']] = array(
            'action' => "module=CGX_AdvancedView&MB=true&action=editLayout&view=" . MB_LISTVIEW . "&view_module={$this->editModule}&view_package={$this->package}",
            'imageTitle' => 'ListView',
            'help' => 'viewBtnListView'
        );
        $this->buttons[$GLOBALS['mod_strings']['LBL_QUICKCREATE']] = array(
            'action' => "module=CGX_AdvancedView&MB=true&action=editLayout&view=" . MB_QUICKCREATE . "&view_module={$this->editModule}&view_package={$this->package}",
            'imageTitle' => 'QuickCreate',
            'help' => 'viewBtnQuickCreate'
        );
        $this->buttons[$GLOBALS['mod_strings']['LBL_SEARCH']] = array(
            'action' => "module=CGX_AdvancedView&MB=true&action=wizard&view=search&view_module={$this->editModule}&view_package={$this->package}",
            'imageTitle' => 'SearchForm',
            'help' => 'searchBtn'
        );
        $this->buttons[$GLOBALS['mod_strings']['LBL_DASHLET']] = array(
            'action' => "module=CGX_AdvancedView&MB=true&action=wizard&view=dashlet&view_module={$this->editModule}&view_package={$this->package}",
            'imageTitle' => 'Dashlet',
            'help' => 'viewBtnDashlet'
        );
        $this->buttons[$GLOBALS['mod_strings']['LBL_POPUP']] = array(
            'imageTitle' => 'Popup',
            'action' => "module=CGX_AdvancedView&MB=true&action=wizard&view=popup&view_module={$this->editModule}&view_package={$this->package}",
            'help' => 'PopupListViewBtn'
        );
    }

    function generateMBDashletButtons()
    {
        $this->buttons[$GLOBALS['mod_strings']['LBL_DASHLETLISTVIEW']] = array(
            'action' => "module=CGX_AdvancedView&MB=true&action=editLayout&view=dashlet&view_module={$this->editModule}&view_package={$this->package}",
            'imageTitle' => $GLOBALS['mod_strings']['LBL_DASHLETLISTVIEW'],
            'imageName' => 'ListView',
            'help' => 'DashletListViewBtn'
        );
        $this->buttons[$GLOBALS['mod_strings']['LBL_DASHLETSEARCHVIEW']] = array(
            'action' => "module=CGX_AdvancedView&MB=true&action=editLayout&view=dashletsearch&view_module={$this->editModule}&view_package={$this->package}",
            'imageTitle' => $GLOBALS['mod_strings']['LBL_DASHLETSEARCHVIEW'],
            'imageName' => 'BasicSearch',
            'help' => 'DashletSearchViewBtn'
        );
    }

    function generateMBPopupButtons()
    {
        $this->buttons[$GLOBALS['mod_strings']['LBL_POPUPLISTVIEW']] = array(
            'action' => "module=CGX_AdvancedView&action=editLayout&view=popuplist&view_module={$this->editModule}&view_package={$this->package}",
            'imageTitle' => $GLOBALS['mod_strings']['LBL_POPUPLISTVIEW'],
            'imageName' => 'ListView',
            'help' => 'PopupListViewBtn'
        );
        $this->buttons[$GLOBALS['mod_strings']['LBL_POPUPSEARCH']] = array(
            'action' => "module=CGX_AdvancedView&action=editLayout&view=popupsearch&view_module={$this->editModule}&view_package={$this->package}",
            'imageTitle' => $GLOBALS['mod_strings']['LBL_POPUPSEARCH'],
            'imageName' => 'BasicSearch',
            'help' => 'PopupSearchViewBtn'
        );
    }

    function generateStudioDashletButtons($groupName)
    {
        $this->buttons[$GLOBALS['mod_strings']['LBL_DASHLETLISTVIEW']] = array(
            'action' => "module=CGX_AdvancedView&action=editLayout&view=dashlet&view_module={$this->editModule}&group_name={$groupName}",
            'imageTitle' => $GLOBALS['mod_strings']['LBL_DASHLETLISTVIEW'],
            'imageName' => 'ListView',
            'help' => 'DashletListViewBtn'
        );
        $this->buttons[$GLOBALS['mod_strings']['LBL_DASHLETSEARCHVIEW']] = array(
            'action' => "module=CGX_AdvancedView&action=editLayout&view=dashletsearch&view_module={$this->editModule}&group_name={$groupName}",
            'imageTitle' => $GLOBALS['mod_strings']['LBL_DASHLETSEARCHVIEW'],
            'imageName' => 'BasicSearch',
            'help' => 'DashletSearchViewBtn'
        );
    }

    function generateStudioPopupButtons($groupName)
    {
        $this->buttons[$GLOBALS['mod_strings']['LBL_POPUPLISTVIEW']] = array(
            'action' => "module=CGX_AdvancedView&action=editLayout&view=popuplist&view_module={$this->editModule}&group_name={$groupName}",
            'imageTitle' => $GLOBALS['mod_strings']['LBL_POPUPLISTVIEW'],
            'imageName' => 'ListView',
            'help' => 'PopupListViewBtn'
        );
        $this->buttons[$GLOBALS['mod_strings']['LBL_POPUPSEARCH']] = array(
            'action' => "module=CGX_AdvancedView&action=editLayout&view=popupsearch&view_module={$this->editModule}&group_name={$groupName}",
            'imageTitle' => $GLOBALS['mod_strings']['LBL_POPUPSEARCH'],
            'imageName' => 'BasicSearch',
            'help' => 'PopupSearchViewBtn'
        );
    }

    function generateMBSearchButtons()
    {
        $this->buttons[$GLOBALS['mod_strings']['LBL_BASIC']] = array(
            'action' => "module=CGX_AdvancedView&MB=true&action=editLayout&view_module={$this->editModule}&view_package={$this->package}&view=SearchView&searchlayout=basic_search",
            'imageTitle' => $GLOBALS['mod_strings']['LBL_BASIC_SEARCH'],
            'imageName' => 'BasicSearch',
            'help' => "BasicSearchBtn"
        );
        $this->buttons[$GLOBALS['mod_strings']['LBL_ADVANCED']] = array(
            'action' => "module=CGX_AdvancedView&MB=true&action=editLayout&view_module={$this->editModule}&view_package={$this->package}&view=SearchView&searchlayout=advanced_search",
            'imageTitle' => $GLOBALS['mod_strings']['LBL_ADVANCED_SEARCH'],
            'imageName' => 'AdvancedSearch',
            'help' => "AdvancedSearchBtn"
        );
    }
}
