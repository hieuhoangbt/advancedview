<?php

require_once 'include/TemplateHandler/TemplateHandler.php';

/**
 * Extends class TemplateHandler
 * @Overwrite method 
 */
class CGX_AdvancedView_TemplateHandler extends TemplateHandler
{

    /**
     * Builds a template
     * This is a private function that should be called only from checkTemplate method
     *
     * @param module string module name
     * @param view string view need (eg DetailView, EditView, etc)
     * @param tpl string generic tpl to use
     * @param ajaxSave boolean parameter indicating whether or not this is coming from an Ajax call
     * @param metaDataDefs metadata definition as Array
     * */
    function buildTemplate($module, $view, $tpl, $ajaxSave, $metaDataDefs)
    {
        global $theme;
        $this->loadSmarty();
        $cacheDir = create_cache_directory($this->themeDir . $theme . '/' . $this->templateDir . $module . '/');
        $file = $cacheDir . $view . '.tpl';
        $string = '{* Create Date: ' . date('Y-m-d H:i:s') . "*}\n";
        $this->ss->left_delimiter = '{{';
        $this->ss->right_delimiter = '}}';
        $this->ss->assign('module', $module);
        $this->ss->assign('built_in_buttons', array('CANCEL', 'DELETE', 'DUPLICATE', 'EDIT', 'FIND_DUPLICATES', 'SAVE', 'CONNECTOR'));
        $contents = $this->ss->fetch($tpl);
        //Insert validation and quicksearch stuff here
        if (strchr($view, 'EditView') != false || strpos($view, 'QuickCreate') || $ajaxSave || $view == "ConvertLead") {
            global $dictionary, $beanList, $app_strings, $mod_strings;
            $mod = $beanList[$module];

            if ($mod == 'aCase') {
                $mod = 'Case';
            }

            $defs = $dictionary[$mod]['fields'];
            $defs2 = array();
            //Retrieve all panel field definitions with displayParams Array field set
            $panelFields = array();

            foreach ($metaDataDefs['panels'] as $panel) {
                foreach ($panel as $row) {
                    foreach ($row as $entry) {
                        if (empty($entry)) {
                            continue;
                        }

                        if (is_array($entry) &&
                                isset($entry['name']) &&
                                isset($entry['displayParams']) &&
                                isset($entry['displayParams']['required']) &&
                                $entry['displayParams']['required']) {
                            $panelFields[$entry['name']] = $entry;
                        }

                        if (is_array($entry)) {
                            $defs2[$entry['name']] = $entry;
                        } else {
                            $defs2[$entry] = array('name' => $entry);
                        }
                    } //foreach
                } //foreach
            } //foreach

            foreach ($panelFields as $field => $value) {
                $nameList = array();
                if (!is_array($value['displayParams']['required'])) {
                    $nameList[] = $field;
                } else {
                    foreach ($value['displayParams']['required'] as $groupedField) {
                        $nameList[] = $groupedField;
                    }
                }

                foreach ($nameList as $x) {
                    if (isset($defs[$x]) &&
                            isset($defs[$x]['type']) &&
                            !isset($defs[$x]['required'])) {
                        $defs[$x]['required'] = true;
                    }
                }
            } //foreach
            //Create a base class with field_name_map property
            $sugarbean = new stdClass;
            $sugarbean->field_name_map = $defs;
            $sugarbean->module_dir = $module;

            $javascript = new javascript();
            $view = $view == 'QuickCreate' ? "QuickCreate_{$module}" : $view;
            $view = (strchr($view, 'EditView') != false) ? "EditView" : $view;
            $view = (strchr($view, 'DetailView') != false) ? "DetailView" : $view;
            $javascript->setFormName($view);

            $javascript->setSugarBean($sugarbean);
            if ($view != "ConvertLead")
                $javascript->addAllFields('', null, true);

            $validatedFields = array();
            $javascript->addToValidateBinaryDependency('assigned_user_name', 'alpha', $javascript->buildStringToTranslateInSmarty('ERR_SQS_NO_MATCH_FIELD') . ': ' . $javascript->buildStringToTranslateInSmarty('LBL_ASSIGNED_TO'), 'false', '', 'assigned_user_id');
            $validatedFields[] = 'assigned_user_name';
            //Add remaining validation dependency for related fields
            //1) a relate type as defined in vardefs
            //2) set in metadata layout
            //3) not have validateDepedency set to false in metadata
            //4) have id_name in vardef entry
            //5) not already been added to Array
            foreach ($sugarbean->field_name_map as $name => $def) {

                if ($def['type'] == 'relate' &&
                        isset($defs2[$name]) &&
                        (!isset($defs2[$name]['validateDependency']) || $defs2[$name]['validateDependency'] === true) &&
                        isset($def['id_name']) &&
                        !in_array($name, $validatedFields)) {

                    if (isset($mod_strings[$def['vname']]) || isset($app_strings[$def['vname']]) || translate($def['vname'], $sugarbean->module_dir) != $def['vname']) {
                        $vname = $def['vname'];
                    } else {
                        $vname = "undefined";
                    }
                    $javascript->addToValidateBinaryDependency($name, 'alpha', $javascript->buildStringToTranslateInSmarty('ERR_SQS_NO_MATCH_FIELD') . ': ' . $javascript->buildStringToTranslateInSmarty($vname), (!empty($def['required']) ? 'true' : 'false'), '', $def['id_name']);
                    $validatedFields[] = $name;
                }
            } //foreach

            $contents .= "{literal}\n";
            $contents .= $javascript->getScript();
            $contents .= $this->createQuickSearchCode($defs, $defs2, $view, $module);
            $contents .= "{/literal}\n";
        } else if (preg_match('/^SearchForm_.+/', $view)) {
            global $dictionary, $beanList, $app_strings, $mod_strings;
            $mod = $beanList[$module];

            if ($mod == 'aCase') {
                $mod = 'Case';
            }

            $defs = $dictionary[$mod]['fields'];
            $contents .= '{literal}';
            $contents .= $this->createQuickSearchCode($defs, array(), $view);
            $contents .= '{/literal}';
        }//if
        //Remove all the copyright comments
        $contents = preg_replace('/\{\*[^\}]*?\*\}/', '', $contents);

        if ($fh = @sugar_fopen($file, 'w')) {
            fputs($fh, $contents);
            fclose($fh);
        }

        $this->ss->left_delimiter = '{';
        $this->ss->right_delimiter = '}';
    }

}
