<?php

/** * ******************************************************************************
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
/**
 * THIS CLASS IS FOR DEVELOPERS TO MAKE CUSTOMIZATIONS IN
 */
require_once ('modules/CGX_AdvancedView/CGX_AdvancedView_sugar.php');

class CGX_AdvancedView extends CGX_AdvancedView_sugar {

    function __construct() {
        parent::__construct();
    }

    /*
     * Get role list from db to config for group
     */

    function getRoleList() {
        $sql = "SELECT id, name FROM acl_roles WHERE deleted=0 ORDER BY name ASC";
        $rs = $GLOBALS['db']->query($sql);
        $role_list = array();
        while ($row = $GLOBALS['db']->fetchByAssoc($rs)) {
            $role_list[] = array(
                'role_id' => $row['id'],
                'role_name' => $row['name']
            );
        }
        return $role_list;
    }

    /*
     * Get role list enabled for group
     */

    function classifyOfRoles($module, $group_folder) {
        $role_list = $this->getRoleList();
        $role_id_list_enabled = $this->retrieveRoleConfiguration(trim($module), trim($group_folder));
        $role_list_enabled = array();
        $role_list_disabled = array();
        foreach ($role_list as $role) {
            if (in_array($role['role_id'], $role_id_list_enabled)) {
                $role_list_enabled[] = $role;
            } else {
                $role_list_disabled[] = $role;
            }
        }
        return array(
            'role_list_enabled' => $role_list_enabled,
            'role_list_disabled' => $role_list_disabled
        );
    }

    /*
     * Save setting
     */

    function saveRoleConfiguration($data) {
        if (!empty($data['group_folder'])) {
            $this->retrieve_by_string_fields(array(
                'name' => $data['module_setting'],
                'group_folder' => $data['group_folder']
            ));
        } else {
            $this->group_folder = trim(str_replace(' ', '', $data['group_name']));
        }
        $toDecode = html_entity_decode($_POST['role_list_enabled'], ENT_QUOTES);
        $role_list_enabled = json_decode($toDecode);
        $serialized = base64_encode(serialize($role_list_enabled));
        $this->name = $data['module_setting'];
        $this->group_name = $data['group_name'];
        $this->weight = 0;
        $this->customer_implementation = isset($data['customer_implementation']) ? $data['customer_implementation'] : 0;
        $this->role_list_enabled = $serialized;
        // Save config to db
        if (empty($this->id)) {
            // Copy metadata default to new group
            $this->cloneMetadataFromDefault($data['module_setting'], $this->group_folder, trim($data['clone_group_folder']));
        } else {
            $GLOBALS['log']->fatal('Can not make group for module' . $data['module_setting']);
        }
        $this->save();
        return $this->group_folder;
    }

    /*
     * Retrive setting
     */

    function retrieveRoleConfiguration($module, $group_folder) {
        $this->retrieve_by_string_fields(array(
            'name' => $module,
            'group_folder' => $group_folder
        ));
        $role_list_enabled = array();
        if (!empty($this->id)) {
            $role_list_enabled = trim($this->role_list_enabled);
            if (!empty($role_list_enabled)) {
                $role_list_enabled = base64_decode($role_list_enabled);
                $role_list_enabled = unserialize($role_list_enabled);
            }
        }
        return $role_list_enabled;
    }

    /*
     * This function using to get layout group by role
     */

    function getGroupNameByCurrentUser($module) {
        global $current_user;
        $role = new ACLRole();
        $roles = $role->getUserRoles($current_user->id);
        $groups = array();
        if (empty($roles)) {
            $groups[] = "Default";
            return $groups;
        }
        $role_id_list = array();
        foreach ($roles as $r) {
            $role_id_list[] = $r['id'];
        }
        $sql = "SELECT group_folder, weight FROM cgx_advancedview WHERE name='$module' ORDER BY weight DESC";
        $rs = $GLOBALS['db']->query($sql);
        if (!empty($rs)) {
            while ($row = $GLOBALS['db']->fetchByAssoc($rs)) {
                $role_list_enabled = base64_decode($row['value']);
                $role_list_enabled = unserialize($role_list_enabled);
                if (array_intersect($role_id_list, $role_list_enabled)) {
                    $groups[] = $row['group_folder'];
                }
            }
            // If not found any group then return group default
            if (empty($groups)) {
                $groups[] = "Default";
            }
            return $groups[0];
        }
    }

    /*
     * This function using clone source from default to new group
     */

    function cloneMetadataFromDefault($module, $group = 'Default', $clone_from_group = 'Default') {
        if (!empty(trim($group)) && trim($group) != 'Default') {
            $source_dir = "modules/" . $module . "/metadata";
            if (!empty($clone_from_group) && $clone_from_group != 'Default') {
                $source_dir = "custom/" . $source_dir . "/group/" . $clone_from_group;
            }
            $target_dir = "custom/modules/" . $module . "/metadata/group/" . $group;
            // Copy metadata file
            if (!is_dir($target_dir)) {
                $this->recurse_copy($source_dir, $target_dir, 'subpanels');
            }
            // Copy Dashlets folder
            if (empty($clone_from_group)) {
                require_once 'modules/CGX_AdvancedView/parsers/ParserFactory.php';
                $_REQUEST['group_name'] = $group;
                $_REQUEST['clone_group'] = 1;
                $parser = ParserFactory::getParser("dashlet", $module, null);
                $parser->handleSave();
            }
        }
        return true;
    }

    /*
     * This function util using to delete group folder
     */

    function deleteGroup($module, $group_folder) {
        // Update soft delete
        $this->retrieve_by_string_fields(array(
            'name' => $module,
            'group_folder' => $group_folder
        ));
        $this->deleted = 1;
        $this->save();
    }

    /*
     * This function util using to copy file folder to folder
     */

    function recurse_copy($src, $dst, $skip_folder = '') {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    if (trim($file) != trim($skip_folder)) {
                        $this->recurse_copy($src . '/' . $file, $dst . '/' . $file, $skip_folder);
                    }
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    function getCustomMetadataFile($module, $type) {
        $groups = $this->getGroupNameByCurrentUser($module);
        $metadataFile = null;
        $viewDef = strtolower($type) . 'viewdefs';
        $coreMetaPath = 'modules/' . $module . '/metadata/' . $viewDef . '.php';
        if (!empty($groups)) {
            if ($groups[0] == 'Default') {
                if (file_exists('custom/' . $coreMetaPath)) {
                    $metadataFile = 'custom/' . $coreMetaPath;
                } else {
                    $metadataFile = $coreMetaPath;
                }
            } else {
                if (file_exists("custom/modules/" . $module . "/metadata/group/{$groups[0]}/" . $viewDef . '.php')) {
                    $metadataFile = "custom/modules/" . $module . "/metadata/group/{$groups[0]}/" . $viewDef . '.php';
                }
            }
        }
        return $metadataFile;
    }

    /*     * *************************************************************************
     * Customization 
     * ************************************************************************* */

    /**
     * @function getGroupNameInModule
     */
    function getGroupNameInModuleByRole($module, $user_roles) {
        $bean = new CGX_AdvancedView();
        $order_by = "weight DESC, group_name ASC";
        $where = "name='{$module}'";
        $query = $bean->create_new_list_query($order_by, $where);
        try {
            $result = $GLOBALS['db']->query($query);
        } catch (Exception $e) {
            $GLOBALS['log']->debug("CGX_ADVANCED_VIEW -> " . $e->getMessage());
        }
        if ($result) {
            while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
                $row['role_list_enabled'] = unserialize(base64_decode($row['role_list_enabled']));
                foreach ($row['role_list_enabled'] as $id) {
                    $role = BeanFactory::getBean("ACLRoles", $id);
                    if (in_array($role->name, $user_roles)) {
                        return $row;
                    }
                }
            }
        }
        return false;
    }

}

?>
