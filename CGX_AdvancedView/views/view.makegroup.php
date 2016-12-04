<?php
require_once ('modules/CGX_AdvancedView/MB/AjaxCompose.php');

class ViewMakeGroup extends SugarView
{

    function __construct()
    {
        $this->options['show_footer'] = true;
        parent::__construct();
    }

    function display()
    {
        $adv_view = new CGX_AdvancedView();
        $module_setting = $_REQUEST['module_setting'];
        $group_folder = isset($_REQUEST['group_name']) ? $_REQUEST['group_name'] : '';
        $clone_group = isset($_REQUEST['clone_group']) ? $_REQUEST['clone_group'] : '';
        $refreshMod = false;
        if ($_POST) {
            $folder = $adv_view->saveRoleConfiguration($_POST);
            require_once ('modules/CGX_AdvancedView/MB/AjaxCompose.php');
            require_once ('modules/CGX_AdvancedView/Module/CGX_AdvancedViewStudioTree.php');
            $mbt = new CGX_AdvancedViewStudioTree();
            echo json_encode(array(
                'west' => $mbt->fetchNodes(),
                'group_folder' => $folder
            ));
            exit();
        } else {
            $adv_view->retrieve_by_string_fields(array(
                'name' => $module_setting,
                'group_folder' => str_replace(' ', '', trim($group_folder))
            ));
            $role_list = $adv_view->classifyOfRoles($module_setting, $group_folder);
            // Check group exist contain group_folder
            $count = '';
            $group_folder = $adv_view->group_folder;
            $group_name = $adv_view->group_name;
            $clone_group_folder = "";
            if($clone_group) {
                $sql = "SELECT COUNT(id) FROM cgx_advancedview WHERE deleted=0 AND group_folder LIKE'{$group_folder}%'";
                $count = $GLOBALS['db']->getOne($sql);
                $group_folder = "";
                $group_name .= " {$count}";
                $clone_group_folder = $adv_view->group_folder;
            }
            // End
            $this->ss->assign('MODULE_SETTING', $module_setting);
            $this->ss->assign('GROUP_NAME', $group_name);
            $this->ss->assign('GROUP_FOLDER', $group_folder);
            $this->ss->assign('CLONE_GROUP', $clone_group);
            $this->ss->assign('CLONE_GROUP_FOLDER', $clone_group_folder);
            $this->ss->assign('CUSTOMER_IMPL', (int)$adv_view->customer_implementation);
            $this->ss->assign('ROLE_LIST_ENABLED', json_encode($role_list['role_list_enabled']));
            $this->ss->assign('ROLE_LIST_DISABLED', json_encode($role_list['role_list_disabled']));
        }
        $ajax = new AjaxCompose();
        $ajax->addCrumb($GLOBALS['mod_strings']['LBL_MODULEBUILDER'], 'ModuleBuilder.main("mb")');
        $html = $this->ss->fetch('modules/CGX_AdvancedView/tpls/make_group.tpl');
        $ajax->addSection('center', translate('LBL_SECTION_MODULE', 'CGX_AdvancedView'), $html);

        echo $ajax->getJavascript();
    }
}
