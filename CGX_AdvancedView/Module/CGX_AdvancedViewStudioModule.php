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
require_once 'data/BeanFactory.php';
require_once 'modules/CGX_AdvancedView/parsers/relationships/DeployedRelationships.php';
require_once 'modules/CGX_AdvancedView/parsers/constants.php';

/**
 * Override some method in StudioModule Class
 */
class CGX_AdvancedViewStudioModule {

    public $name;
    private $popups = array();
    public $module;
    public $fields;
    public $seed;

    function __construct($module) {
        //Sources can be used to override the file name mapping for a specific view or the parser for a view.
        //The
        $this->sources = array('editviewdefs.php' => array('name' => translate('LBL_EDITVIEW'), 'type' => MB_EDITVIEW, 'image' => 'EditView'),
            'detailviewdefs.php' => array('name' => translate('LBL_DETAILVIEW'), 'type' => MB_DETAILVIEW, 'image' => 'DetailView'),
            'listviewdefs.php' => array('name' => translate('LBL_LISTVIEW'), 'type' => MB_LISTVIEW, 'image' => 'ListView'));

        $moduleNames = array_change_key_case($GLOBALS ['app_list_strings'] ['moduleList']);
        $this->name = isset($moduleNames [strtolower($module)]) ? $moduleNames [strtolower($module)] : strtolower($module);
        $this->module = $module;
        $this->seed = BeanFactory::getBean($this->module);
        if ($this->seed) {
            $this->fields = $this->seed->field_defs;
        }
        //$GLOBALS['log']->debug ( get_class($this)."->__construct($module): ".print_r($this->fields,true) ) ;
    }

    /*
     * Gets the name of this module. Some modules have naming inconsistencies such as Bugs and Bugs which causes warnings in Relationships
     * Added to resolve bug #20257
     */
    function getModuleName() {
        $modules_with_odd_names = array(
            'Bugs' => 'Bugs'
        );
        if (isset($modules_with_odd_names [$this->name]))
            return ( $modules_with_odd_names [$this->name] );

        return $this->name;
    }

    /*
     * Attempt to determine the type of a module, for example 'basic' or 'company'
     * These types are defined by the SugarObject Templates in /include/SugarObjects/templates
     * Custom modules extend one of these standard SugarObject types, so the type can be determined from their parent
     * Standard module types can be determined simply from the module name - 'bugs' for example is of type 'issue'
     * If all else fails, fall back on type 'basic'...
     * @return string Module's type
     */
    function getType() {
        // first, get a list of a possible parent types
        $templates = array();
        $d = dir('include/SugarObjects/templates');
        while ($filename = $d->read()) {
            if (substr($filename, 0, 1) != '.')
                $templates [strtolower($filename)] = strtolower($filename);
        }

        // If a custom module, then its type is determined by the parent SugarObject that it extends
        $type = $GLOBALS ['beanList'] [$this->module];
        require_once $GLOBALS ['beanFiles'] [$type];

        do {
            $seed = new $type ();
            $type = get_parent_class($seed);
        } while (!in_array(strtolower($type), $templates) && $type != 'SugarBean');

        if ($type != 'SugarBean') {
            return strtolower($type);
        }

        // If a standard module then just look up its type - type is implicit for standard modules. Perhaps one day we will make it explicit, just as we have done for custom modules...
        $types = array(
            'Accounts' => 'company',
            'Bugs' => 'issue',
            'Cases' => 'issue',
            'Contacts' => 'person',
            'Documents' => 'file',
            'Leads' => 'person',
            'Opportunities' => 'sale'
        );
        if (isset($types [$this->module]))
            return $types [$this->module];

        return "basic";
    }

    /*
     * Return the fields for this module as sourced from the SugarBean
     * @return	Array of fields
     */
    function getFields() {
        return $this->fields;
    }

    /**
     * Override getNodes() for custom action
     * @return array
     */
    function getNodes() {
        return array('name' => $this->name, 'module' => $this->module, 'type' => 'StudioModule', 'action' => "module=CGX_AdvancedView&action=wizard&view_module={$this->module}", 'children' => $this->getModule());
    }

    /**
     * Override getModule() for custom action
     * Ignore Label
     * Ignore Field
     * Ignore Relationship
     * Ignore Subpanel
     * @return array
     */
    function getModule() {
        $sources = array(
            translate('LBL_LAYOUTS') => array('children' => 'genGroup', 'action' => "module=CGX_AdvancedView&action=wizard&view=layouts&view_module={$this->module}", 'imageTitle' => 'Layouts', 'help' => 'layoutsBtn')
        );
        $nodes = array();
        foreach ($sources as $source => $def) {
            $nodes [$source] = $def;
            $nodes [$source] ['name'] = translate($source);
            if (!isset($def ['children'])) {
                return $nodes;
            }
            $defChildren = $def ['children'];
            $childNodes = $this->$defChildren();
            if (!empty($childNodes)) {
                $nodes [$source] ['type'] = 'Folder';
                $cChild = array();
                foreach ($childNodes as $child => $def2) {
                    $child = $def2;
                    if (isset($def2['children'])) {
                        $defChild2 = $def2['children'];
                        $defParams2 = $def2['groupName'];
                        $childNode = $this->$defChild2($defParams2);
                        $child['children'] = $childNode;
                    }
                    $cChild[] = $child;
                }
                $nodes[$source]['children'] = $cChild;
            } else {
                unset($nodes [$source]);
            }
        }

        return $nodes;
    }

    /**
     * Override function getViews()
     * @return $view
     */
    function getViews($groupName) {
        $views = array();
        foreach ($this->sources as $file => $def) {
            if (file_exists("custom/modules/{$this->module}/metadata/group/$groupName/$file")) {
                $views [str_replace('.php', '', $file)] = $def;
            }
        }
        return $views;
    }

    function recusiveGenerateDir($path) {
        $exp = explode("/", $path);
        $newPath = "custom/modules";
        for ($i = 0; $i < count($exp); $i++) {
            $newPath .= "/" . $exp[$i];
            if (!is_dir($newPath)) {
                mkdir($newPath, "0755");
            }
        }
        return $newPath;
    }

    /**
     * genGroup in metadata
     * @return listGroup
     */
    function genGroup() {
        $groupCustomPath = "{$this->module}/metadata/group";
        $groupCustomPath = $this->recusiveGenerateDir($groupCustomPath);
        if (!is_dir($groupCustomPath . "/Default")) {
            mkdir($groupCustomPath . "/Default", "0755");
        }
//        $dirs = scandir($groupCustomPath);
//        $childNodes = array();
//        foreach ($dirs as $key => $dir) {
//            if ($dir == "." || $dir == "..") {
//                unset($dirs[$key]);
//            } else {
//                $childNodes[$dir] = array('name' => $dir, 'type' => 'Folder', 'children' => 'getLayouts', 'groupName' => $dir, 'action' => "module=CGX_AdvancedView&action=wizard&view=groups&view_module={$this->module}&group_name={$dir}", 'imageTitle' => 'Layouts', 'help' => 'groupsBtn', 'isGroup' => true);
//            }
//        }

        $adv_view_bean = new CGX_AdvancedView();
        $order_by = "weight DESC";
        $where = "name='{$this->module}'";
        $query_list_group_by_module = $adv_view_bean->create_new_list_query($order_by, $where);
        try {
            $result = $GLOBALS['db']->query($query_list_group_by_module);
        } catch (Exception $e) {
            $GLOBALS['log']->debug($e->getMessage());
        }

        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            $childNodes[$row['group_name']] = array('name' => $row['group_name'], 'type' => 'Folder', 'children' => 'getLayouts', 'groupName' => $row['group_name'], 'groupFolder' => $row['group_folder'], 'action' => "module=CGX_AdvancedView&action=wizard&view=groups&view_module={$this->module}&group_name={$row['group_folder']}", 'imageTitle' => 'Layouts', 'help' => 'groupsBtn', 'isGroup' => true);
        }
        $dir = "Default";
        $childNodes[$dir] = array('name' => $dir, 'type' => 'Folder', 'children' => 'getLayouts', 'groupName' => $dir, 'groupFolder' => $dir, 'action' => "module=CGX_AdvancedView&action=wizard&view=groups&view_module={$this->module}&group_name={$dir}", 'imageTitle' => 'Layouts', 'help' => 'groupsBtn', 'isGroup' => true);
        return $childNodes;
    }

    function getLayouts($groupName) {
        if (trim($groupName) == '') {
            $groupName = 'Default';
        }
        $views = $this->getViews($groupName);

        // Now add in the QuickCreates - quickcreatedefs can be created by Studio from editviewdefs if they are absent, so just add them in regardless of whether the quickcreatedefs file exists

        $hideQuickCreateForModules = array('kbdocuments', 'projecttask',
            'campaigns'
        );
        // Some modules should not have a QuickCreate form at all, so do not add them to the list
        if (!in_array(strtolower($this->module), $hideQuickCreateForModules) && file_exists("custom/modules/{$this->module}/metadata/group/$groupName/quickcreatedefs.php")) {
            $views ['quickcreatedefs'] = array('name' => translate('LBL_QUICKCREATE'), 'type' => MB_QUICKCREATE, 'image' => 'QuickCreate');
        }
        $layouts = array();
        foreach ($views as $def) {
            $view = !empty($def['view']) ? $def['view'] : $def['type'];
            $layouts [$def['name']] = array('name' => $def['name'], 'action' => "module=CGX_AdvancedView&action=editLayout&view={$view}&view_module={$this->module}&group_name={$groupName}", 'imageTitle' => $def['image'], 'help' => "viewBtn{$def['type']}", 'size' => '48');
        }

        if ($this->isValidDashletModule($this->module)) {
            $dashlets = array();
            $dashlets [] = array('name' => translate('LBL_DASHLETLISTVIEW'), 'type' => 'dashlet', 'action' => 'module=CGX_AdvancedView&action=editLayout&view=dashlet&view_module=' . $this->module . '&group_name=' . $groupName);
            $dashlets [] = array('name' => translate('LBL_DASHLETSEARCHVIEW'), 'type' => 'dashletsearch', 'action' => 'module=CGX_AdvancedView&action=editLayout&view=dashletsearch&view_module=' . $this->module . '&group_name=' . $groupName);
            $layouts [translate('LBL_DASHLET')] = array('name' => translate('LBL_DASHLET'), 'type' => 'Folder', 'children' => $dashlets, 'imageTitle' => 'Dashlet', 'action' => 'module=CGX_AdvancedView&action=wizard&view=dashlet&view_module=' . $this->module . '&group_name=' . $groupName);
        }

        //For popup tree node
        if ($this->isValidPopupModule($this->module, $groupName)) {
            $popups = array();
            $popups [] = array('name' => translate('LBL_POPUPLISTVIEW'), 'type' => 'popuplistview', 'action' => 'module=CGX_AdvancedView&action=editLayout&view=popuplist&view_module=' . $this->module . '&group_name=' . $groupName);
            $popups [] = array('name' => translate('LBL_POPUPSEARCH'), 'type' => 'popupsearch', 'action' => 'module=CGX_AdvancedView&action=editLayout&view=popupsearch&view_module=' . $this->module . '&group_name=' . $groupName);
            $layouts [translate('LBL_POPUP')] = array('name' => translate('LBL_POPUP'), 'type' => 'Folder', 'children' => $popups, 'imageTitle' => 'Popup', 'action' => 'module=CGX_AdvancedView&action=wizard&view=popup&view_module=' . $this->module . '&group_name=' . $groupName);
        }

        $nodes = $this->getSearch($groupName);
        if (!empty($nodes)) {
            $layouts [translate('LBL_SEARCH')] = array('name' => translate('LBL_SEARCH'), 'type' => 'Folder', 'children' => $nodes, 'action' => "module=CGX_AdvancedView&action=wizard&view=search&view_module={$this->module}&group_name={$groupName}", 'imageTitle' => 'BasicSearch', 'help' => 'searchBtn', 'size' => '48');
        }

        return $layouts;
    }

    function isValidPopupModule($moduleName, $groupName) {
        if (file_exists("custom/modules/$moduleName/metadata/group/$groupName/popupdefs.php")) {
            return true;
        }
        return false;
    }

    function isValidDashletModule($moduleName) {
        $fileName = "My{$moduleName}Dashlet";
        $customFileName = "{$moduleName}Dashlet";
        if (file_exists("modules/{$moduleName}/Dashlets/{$fileName}/{$fileName}.php") || file_exists("custom/modules/{$moduleName}/Dashlets/{$fileName}/{$fileName}.php") || file_exists("modules/{$moduleName}/Dashlets/{$customFileName}/{$customFileName}.php") || file_exists("custom/modules/{$moduleName}/Dashlets/{$customFileName}/{$customFileName}.php")) {
            return true;
        }
        return false;
    }

    function getSearch($groupName) {
        require_once ('modules/CGX_AdvancedView/parsers/views/SearchViewMetaDataParser.php');

        $nodes = array();
        foreach (array(MB_BASICSEARCH => 'LBL_BASIC_SEARCH', MB_ADVANCEDSEARCH => 'LBL_ADVANCED_SEARCH') as $view => $label) {
            try {
                $parser = new SearchViewMetaDataParser($view, $this->module);
                $title = translate($label);
                if ($label == 'LBL_BASIC_SEARCH') {
                    $name = 'BasicSearch';
                } elseif ($label == 'LBL_ADVANCED_SEARCH') {
                    $name = 'AdvancedSearch';
                } else {
                    $name = str_replace(' ', '', $title);
                }
                $nodes [$title] = array('name' => $title, 'action' => "module=CGX_AdvancedView&action=editLayout&view={$view}&view_module={$this->module}&group_name={$groupName}", 'imageTitle' => $title, 'imageName' => $name, 'help' => "{$name}Btn", 'size' => '48');
            } catch (Exception $e) {
                $GLOBALS ['log']->info('No search layout : ' . $e->getMessage());
            }
        }

        return $nodes;
    }

    function removeFieldFromLayouts($fieldName) {
        require_once("modules/CGX_AdvancedView/parsers/ParserFactory.php");
        $GLOBALS ['log']->info(get_class($this) . "->removeFieldFromLayouts($fieldName)");
        $sources = $this->getViewMetadataSources();
        $sources[] = array('type' => MB_BASICSEARCH);
        $sources[] = array('type' => MB_ADVANCEDSEARCH);
        $sources[] = array('type' => MB_POPUPSEARCH);

        $GLOBALS ['log']->debug(print_r($sources, true));
        foreach ($sources as $name => $defs) {
            //If this module type doesn't support a given metadata type, we will get an exception from getParser()
            try {
                $parser = ParserFactory::getParser($defs ['type'], $this->module);
                if ($parser->removeField($fieldName))
                    $parser->handleSave(false); // don't populate from $_REQUEST, just save as is...
            } catch (Exception $e) {
                
            }
        }

        //Remove the fields in subpanel
        $data = $this->getParentModulesOfSubpanel($this->module);
        foreach ($data as $parentModule) {
            //If this module type doesn't support a given metadata type, we will get an exception from getParser()
            try {
                $parser = ParserFactory::getParser(MB_LISTVIEW, $parentModule, null, $this->module);
                if ($parser->removeField($fieldName)) {
                    $parser->handleSave(false);
                }
            } catch (Exception $e) {
                
            }
        }
    }

    public function getViewMetadataSources() {
        $sources = $this->getViews();
        $sources[] = array('type' => MB_BASICSEARCH);
        $sources[] = array('type' => MB_ADVANCEDSEARCH);
        $sources[] = array('type' => MB_DASHLET);
        $sources[] = array('type' => MB_DASHLETSEARCH);
        $sources[] = array('type' => MB_POPUPLIST);
        $sources[] = array('type' => MB_QUICKCREATE);

        return $sources;
    }

    public function getViewType($view) {
        foreach ($this->sources as $file => $def) {
            if (!empty($def['view']) && $def['view'] == $view && !empty($def['type'])) {
                return $def['type'];
            }
        }
        return $view;
    }

}

?>
