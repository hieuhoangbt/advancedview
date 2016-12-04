<?php

require_once 'include/EditView/EditView2.php';
require_once 'modules/CGX_AdvancedView/App/CGX_AdvancedView_TemplateHandler.php';

/**
 * Extends class EditView
 * @Overwrite method getTemplateHandler
 */
class CGX_AdvancedView_EditView extends EditView
{

    function render()
    {
        $totalWidth = 0;
        foreach ($this->defs['templateMeta']['widths'] as $col => $def) {
            foreach ($def as $k => $value) {
                $totalWidth += $value;
            }
        }

        // calculate widths
        foreach ($this->defs['templateMeta']['widths'] as $col => $def) {
            foreach ($def as $k => $value) {
                $this->defs['templateMeta']['widths'][$col][$k] = round($value / ($totalWidth / 100), 2);
            }
        }

        $this->sectionPanels = array();
        $this->sectionLabels = array();
        if (!empty($this->defs['panels']) && count($this->defs['panels']) > 0) {
            $keys = array_keys($this->defs['panels']);
            if (is_numeric($keys[0])) {
                $defaultPanel = $this->defs['panels'];
                unset($this->defs['panels']); //blow away current value
                $this->defs['panels'][''] = $defaultPanel;
            }
        }

        if (strchr($view, 'EditView')!=false && !empty($GLOBALS['sugar_config']['forms']['requireFirst'])) {
            $this->requiredFirst();
        }

        $maxColumns = isset($this->defs['templateMeta']['maxColumns']) ? $this->defs['templateMeta']['maxColumns'] : 2;
        $panelCount = 0;
        static $itemCount = 100; //Start the generated tab indexes at 100 so they don't step on custom ones.

        /* loop all the panels */
        foreach ($this->defs['panels'] as $key => $p) {
            $panel = array();

            if (!is_array($this->defs['panels'][$key])) {
                $this->sectionPanels[strtoupper($key)] = $p;
            } else {
                foreach ($p as $row => $rowDef) {
                    $columnsInRows = count($rowDef);
                    $columnsUsed = 0;
                    foreach ($rowDef as $col => $colDef) {
                        $panel[$row][$col] = is_array($p[$row][$col]) ? array('field' => $p[$row][$col]) : array('field' => array('name' => $p[$row][$col]));

                        $panel[$row][$col]['field']['tabindex'] = (isset($p[$row][$col]['tabindex']) && is_numeric($p[$row][$col]['tabindex'])) ? $p[$row][$col]['tabindex'] : '0';

                        if ($columnsInRows < $maxColumns) {
                            if ($col == $columnsInRows - 1) {
                                $panel[$row][$col]['colspan'] = 2 * $maxColumns - ($columnsUsed + 1);
                            } else {
                                $panel[$row][$col]['colspan'] = floor(($maxColumns * 2 - $columnsInRows) / $columnsInRows);
                                $columnsUsed = $panel[$row][$col]['colspan'];
                            }
                        }

                        //Set address types to have colspan value of 2 if colspan is not already defined
                        if (is_array($colDef) && !empty($colDef['hideLabel']) && !isset($panel[$row][$col]['colspan'])) {
                            $panel[$row][$col]['colspan'] = 2;
                        }

                        $itemCount++;
                    }
                }

                $panel = $this->getPanelWithFillers($panel);

                $this->sectionPanels[strtoupper($key)] = $panel;
            }


            $panelCount++;
        } //foreach
        $this->defs['templateMeta']['form']['headerTpl'] = "modules/CGX_AdvancedView/tpls/{$_REQUEST['action']}/header.tpl";
        $this->defs['templateMeta']['form']['footerTpl'] = "modules/CGX_AdvancedView/tpls/{$_REQUEST['action']}/footer.tpl";
    }

    function process($checkFormName = false, $formName = '')
    {
        global $mod_strings, $sugar_config, $app_strings, $app_list_strings;
        if (!empty($this->formName)) {
            $formName = $this->formName;
            $checkFormName = true;
        }
        if (!$this->th->checkTemplate($this->module, $this->view, $checkFormName, $formName)) {
            $this->render();
        }

        if (isset($_REQUEST['offset'])) {
            $this->offset = $_REQUEST['offset'] - 1;
        }

        if ($this->showVCRControl) {
            $this->th->ss->assign('PAGINATION', SugarVCR::menu($this->module, $this->offset, $this->focus->is_AuditEnabled(), (strchr($this->view, 'EditView')!=false)));
        }

        if (isset($_REQUEST['return_module']))
            $this->returnModule = $_REQUEST['return_module'];
        if (isset($_REQUEST['return_action']))
            $this->returnAction = $_REQUEST['return_action'];
        if (isset($_REQUEST['return_id']))
            $this->returnId = $_REQUEST['return_id'];
        if (isset($_REQUEST['return_relationship']))
            $this->returnRelationship = $_REQUEST['return_relationship'];
        if (isset($_REQUEST['return_name']))
            $this->returnName = $this->getValueFromRequest($_REQUEST, 'return_name');

        // handle Create $module then Cancel
        if (empty($this->returnId)) {
            $this->returnAction = 'index';
        }

        $is_owner = $this->focus->isOwner($GLOBALS['current_user']->id);

        $this->fieldDefs = array();
        if ($this->focus) {
            global $current_user;

            if (!empty($this->focus->assigned_user_id)) {
                $this->focus->assigned_user_name = get_assigned_user_name($this->focus->assigned_user_id);
            }

            if (!empty($this->focus->job) && $this->focus->job_function == '') {
                $this->focus->job_function = $this->focus->job;
            }

            foreach ($this->focus->toArray() as $name => $value) {
                $valueFormatted = false;
                //if ($this->focus->field_defs[$name]['type']=='link')continue;

                $this->fieldDefs[$name] = (!empty($this->fieldDefs[$name]) && !empty($this->fieldDefs[$name]['value'])) ? array_merge($this->focus->field_defs[$name], $this->fieldDefs[$name]) : $this->focus->field_defs[$name];

                foreach (array("formula", "default", "comments", "help") as $toEscape) {
                    if (!empty($this->fieldDefs[$name][$toEscape])) {
                        $this->fieldDefs[$name][$toEscape] = htmlentities($this->fieldDefs[$name][$toEscape], ENT_QUOTES, 'UTF-8');
                    }
                }

                if (isset($this->fieldDefs[$name]['options']) && isset($app_list_strings[$this->fieldDefs[$name]['options']])) {
                    if (isset($GLOBALS['sugar_config']['enable_autocomplete']) && $GLOBALS['sugar_config']['enable_autocomplete'] == true) {
                        $this->fieldDefs[$name]['autocomplete'] = true;
                        $this->fieldDefs[$name]['autocomplete_options'] = $this->fieldDefs[$name]['options']; // we need the name for autocomplete
                    } else {
                        $this->fieldDefs[$name]['autocomplete'] = false;
                    }
                    // Bug 57472 - $this->fieldDefs[$name]['autocomplete_options' was set too late, it didn't retrieve the list's name, but the list itself (the developper comment show us that developper expected to retrieve list's name and not the options array)
                    $this->fieldDefs[$name]['options'] = $app_list_strings[$this->fieldDefs[$name]['options']];
                }

                if (isset($this->fieldDefs[$name]['options']) && is_array($this->fieldDefs[$name]['options']) && isset($this->fieldDefs[$name]['default_empty']) && !isset($this->fieldDefs[$name]['options'][$this->fieldDefs[$name]['default_empty']])) {
                    $this->fieldDefs[$name]['options'] = array_merge(array($this->fieldDefs[$name]['default_empty'] => $this->fieldDefs[$name]['default_empty']), $this->fieldDefs[$name]['options']);
                }

                if (isset($this->fieldDefs[$name]['function'])) {
                    $function = $this->fieldDefs[$name]['function'];
                    if (is_array($function) && isset($function['name'])) {
                        $function = $this->fieldDefs[$name]['function']['name'];
                    } else {
                        $function = $this->fieldDefs[$name]['function'];
                    }

                    if (isset($this->fieldDefs[$name]['function']['include']) && file_exists($this->fieldDefs[$name]['function']['include'])) {
                        require_once($this->fieldDefs[$name]['function']['include']);
                    }

                    if (!empty($this->fieldDefs[$name]['function']['returns']) && $this->fieldDefs[$name]['function']['returns'] == 'html') {
                        if (!empty($this->fieldDefs[$name]['function']['include'])) {
                            require_once($this->fieldDefs[$name]['function']['include']);
                        }
                        $value = call_user_func($function, $this->focus, $name, $value, $this->view);
                        $valueFormatted = true;
                    } else {
                        $this->fieldDefs[$name]['options'] = call_user_func($function, $this->focus, $name, $value, $this->view);
                    }
                }

                if (isset($this->fieldDefs[$name]['type']) && $this->fieldDefs[$name]['type'] == 'function' && isset($this->fieldDefs[$name]['function_name'])) {
                    $value = $this->callFunction($this->fieldDefs[$name]);
                    $valueFormatted = true;
                }

                if (!$valueFormatted) {
                    // $this->focus->format_field($this->focus->field_defs[$name]);
                    $value = isset($this->focus->$name) ? $this->focus->$name : '';
                }

                if (empty($this->fieldDefs[$name]['value'])) {
                    $this->fieldDefs[$name]['value'] = $value;
                }

                if ((($this->populateBean && empty($this->focus->id)) || (isset($_REQUEST['full_form']))) && (!isset($this->fieldDefs[$name]['function']['returns']) || $this->fieldDefs[$name]['function']['returns'] != 'html') && isset($_REQUEST[$name])) {
                    $this->fieldDefs[$name]['value'] = $this->getValueFromRequest($_REQUEST, $name);
                }
                if (isset($this->returnModule) && isset($this->returnName) && empty($this->focus->id) && empty($this->fieldDefs['name']['value'])) {
                    if (($this->focus->field_defs[$name]['type'] == 'relate') && isset($this->focus->field_defs[$name]['module']) && $this->focus->field_defs[$name]['module'] == $this->returnModule) {
                        if (isset($this->fieldDefs[$name]['id_name']) && !empty($this->returnRelationship) && isset($this->focus->field_defs[$this->fieldDefs[$name]['id_name']]['relationship']) && ($this->returnRelationship == $this->focus->field_defs[$this->fieldDefs[$name]['id_name']]['relationship'])) {
                            $this->fieldDefs[$name]['value'] = $this->returnName;
                            // set the hidden id field for this relate field to the correct value i.e., return_id
                            $this->fieldDefs[$this->fieldDefs[$name]['id_name']]['value'] = $this->returnId;
                        }
                    }
                }
            }
        }

        if (isset($this->focus->additional_meta_fields)) {
            $this->fieldDefs = array_merge($this->fieldDefs, $this->focus->additional_meta_fields);
        }

        if ($this->isDuplicate) {
            foreach ($this->fieldDefs as $name => $defs) {
                if (!empty($defs['auto_increment'])) {
                    $this->fieldDefs[$name]['value'] = '';
                }
            }
        }
    }

    /**
     * display
     * This method makes the Smarty variable assignments and then displays the
     * generated view.
     * @param $showTitle boolean value indicating whether or not to show a title on the resulting page
     * @param $ajaxSave boolean value indicating whether or not the operation is an Ajax save request
     * @return HTML display for view as String
     */
    function display($showTitle = true, $ajaxSave = false)
    {
        global $mod_strings, $sugar_config, $app_strings, $app_list_strings, $theme, $current_user;

        if (isset($this->defs['templateMeta']['javascript'])) {
            if (is_array($this->defs['templateMeta']['javascript'])) {
                //$this->th->ss->assign('externalJSFile', 'modules/' . $this->module . '/metadata/editvewdefs.js');
                $this->th->ss->assign('externalJSFile', $this->defs['templateMeta']['javascript']);
            } else {
                $this->th->ss->assign('scriptBlocks', $this->defs['templateMeta']['javascript']);
            }
        }

        $this->th->ss->assign('id', $this->fieldDefs['id']['value']);
        $this->th->ss->assign('offset', $this->offset + 1);
        $this->th->ss->assign('APP', $app_strings);
        $this->th->ss->assign('MOD', $mod_strings);
        $this->th->ss->assign('fields', $this->fieldDefs);
        $this->th->ss->assign('sectionPanels', $this->sectionPanels);
        $this->th->ss->assign('config', $sugar_config);
        $this->th->ss->assign('returnModule', $this->returnModule);
        $this->th->ss->assign('returnAction', $this->returnAction);
        $this->th->ss->assign('returnId', $this->returnId);
        $this->th->ss->assign('isDuplicate', $this->isDuplicate);
        $this->th->ss->assign('def', $this->defs);
        $this->th->ss->assign('useTabs', isset($this->defs['templateMeta']['useTabs']) && isset($this->defs['templateMeta']['tabDefs']) ? $this->defs['templateMeta']['useTabs'] : false);
        $this->th->ss->assign('maxColumns', isset($this->defs['templateMeta']['maxColumns']) ? $this->defs['templateMeta']['maxColumns'] : 2);
        $this->th->ss->assign('module', $this->module);
        $this->th->ss->assign('headerTpl', isset($this->defs['templateMeta']['form']['headerTpl']) ? $this->defs['templateMeta']['form']['headerTpl'] : 'include/' . $this->view . '/header.tpl');
        $this->th->ss->assign('footerTpl', isset($this->defs['templateMeta']['form']['footerTpl']) ? $this->defs['templateMeta']['form']['footerTpl'] : 'include/' . $this->view . '/footer.tpl');
        $this->th->ss->assign('current_user', $current_user);
        $this->th->ss->assign('bean', $this->focus);
        $this->th->ss->assign('isAuditEnabled', $this->focus->is_AuditEnabled());
        $this->th->ss->assign('gridline', $current_user->getPreference('gridline') == 'on' ? '1' : '0');
        $this->th->ss->assign('tabDefs', isset($this->defs['templateMeta']['tabDefs']) ? $this->defs['templateMeta']['tabDefs'] : false);
        $this->th->ss->assign('VERSION_MARK', getVersionedPath(''));

        global $js_custom_version;
        global $sugar_version;

        $this->th->ss->assign('SUGAR_VERSION', $sugar_version);
        $this->th->ss->assign('JS_CUSTOM_VERSION', $js_custom_version);

        //this is used for multiple forms on one page
        if (!empty($this->formName)) {
            $form_id = $this->formName;
            $form_name = $this->formName;
        } else {
            $form_id = $this->view;
            $form_name = $this->view;
        }

        if ($ajaxSave && empty($this->formName)) {
            $form_id = 'form_' . $this->view . '_' . $this->module;
            $form_name = $form_id;
            $this->view = $form_name;
        }

        $form_name = $form_name == 'QuickCreate' ? "QuickCreate_{$this->module}" : $form_name;
        $form_id = $form_id == 'QuickCreate' ? "QuickCreate_{$this->module}" : $form_id;

        if (isset($this->defs['templateMeta']['preForm'])) {
            $this->th->ss->assign('preForm', $this->defs['templateMeta']['preForm']);
        }

        if (isset($this->defs['templateMeta']['form']['closeFormBeforeCustomButtons'])) {
            $this->th->ss->assign('closeFormBeforeCustomButtons', $this->defs['templateMeta']['form']['closeFormBeforeCustomButtons']);
        }

        if (isset($this->defs['templateMeta']['form']['enctype'])) {
            $this->th->ss->assign('enctype', 'enctype="' . $this->defs['templateMeta']['form']['enctype'] . '"');
        }

        //for SugarFieldImage, we must set form enctype to "multipart/form-data"
        foreach ($this->fieldDefs as $field) {
            if (isset($field['type']) && $field['type'] == 'image') {
                $this->th->ss->assign('enctype', 'enctype="multipart/form-data"');
                break;
            }
        }

        $this->th->ss->assign('showDetailData', $this->showDetailData);
        $this->th->ss->assign('showSectionPanelsTitles', $this->showSectionPanelsTitles);
        $this->th->ss->assign('form_id', $form_id);
        $this->th->ss->assign('form_name', $form_name);
        $this->th->ss->assign('set_focus_block', get_set_focus_js());

        $this->th->ss->assign('form', isset($this->defs['templateMeta']['form']) ? $this->defs['templateMeta']['form'] : null);
        $this->th->ss->assign('includes', isset($this->defs['templateMeta']['includes']) ? $this->defs['templateMeta']['includes'] : null);
        $this->th->ss->assign('view', $this->view);


        //Calculate time & date formatting (may need to calculate this depending on a setting)
        global $timedate;

        $this->th->ss->assign('CALENDAR_DATEFORMAT', $timedate->get_cal_date_format());
        $this->th->ss->assign('USER_DATEFORMAT', $timedate->get_user_date_format());
        $time_format = $timedate->get_user_time_format();
        $this->th->ss->assign('TIME_FORMAT', $time_format);

        $date_format = $timedate->get_cal_date_format();
        $time_separator = ':';
        if (preg_match('/\d+([^\d])\d+([^\d]*)/s', $time_format, $match)) {
            $time_separator = $match[1];
        }

        // Create Smarty variables for the Calendar picker widget
        $t23 = strpos($time_format, '23') !== false ? '%H' : '%I';
        if (!isset($match[2]) || $match[2] == '') {
            $this->th->ss->assign('CALENDAR_FORMAT', $date_format . ' ' . $t23 . $time_separator . '%M');
        } else {
            $pm = $match[2] == 'pm' ? '%P' : '%p';
            $this->th->ss->assign('CALENDAR_FORMAT', $date_format . ' ' . $t23 . $time_separator . '%M' . $pm);
        }

        $this->th->ss->assign('CALENDAR_FDOW', $current_user->get_first_day_of_week());
        $this->th->ss->assign('TIME_SEPARATOR', $time_separator);

        $seps = get_number_seperators();
        $this->th->ss->assign('NUM_GRP_SEP', $seps[0]);
        $this->th->ss->assign('DEC_SEP', $seps[1]);

        if (strchr($this->view, 'EditView')!=false) {
            $height = $current_user->getPreference('text_editor_height');
            $width = $current_user->getPreference('text_editor_width');

            $height = isset($height) ? $height : '300px';
            $width = isset($width) ? $width : '95%';

            $this->th->ss->assign('RICH_TEXT_EDITOR_HEIGHT', $height);
            $this->th->ss->assign('RICH_TEXT_EDITOR_WIDTH', $width);
        } else {
            $this->th->ss->assign('RICH_TEXT_EDITOR_HEIGHT', '100px');
            $this->th->ss->assign('RICH_TEXT_EDITOR_WIDTH', '95%');
        }

        $this->th->ss->assign('SHOW_VCR_CONTROL', $this->showVCRControl);

        $str = $this->showTitle($showTitle);

        //Use the output filter to trim the whitespace
        $this->th->ss->load_filter('output', 'trimwhitespace');
        $str .= $this->th->displayTemplate($this->module, $this->c_tpl, $this->tpl, $ajaxSave, $this->defs);
        /* BEGIN - SECURITY GROUPS */
        //if popup select add panel if user is a member of multiple groups to metadataFile
        global $sugar_config;
        if (isset($sugar_config['securitysuite_popup_select']) && $sugar_config['securitysuite_popup_select'] == true && empty($this->focus->fetched_row['id']) && $this->focus->module_dir != "Users" && $this->focus->module_dir != "SugarFeed") {

            //there are cases such as uploading an attachment to an email template where the request module may
            //not be the same as the current bean module. If that happens we can just skip it
            //however...let quickcreate through
            if ($this->view != 'QuickCreate' && (empty($_REQUEST['module']) || $_REQUEST['module'] != $this->focus->module_dir))
                return $str;

            require_once('modules/SecurityGroups/SecurityGroup.php');
            $groupFocus = new SecurityGroup();
            $security_modules = $groupFocus->getSecurityModules();
            if (in_array($this->focus->module_dir, array_keys($security_modules))) {
                global $current_user;

                $group_count = $groupFocus->getMembershipCount($current_user->id);
                if ($group_count > 1) {

                    $groups = $groupFocus->getUserSecurityGroups($current_user->id);
                    $group_options = '';
                    foreach ($groups as $group) {
                        $group_options .= '<option value="' . $group['id'] . '" label="' . $group['name'] . '" selected="selected">' . $group['name'] . '</option>';
                    }
                    //multilingual support
                    global $current_language;
                    $ss_mod_strings = return_module_language($current_language, 'SecurityGroups');

                    $lbl_securitygroups_select = $ss_mod_strings['LBL_GROUP_SELECT'];
                    $lbl_securitygroups = $ss_mod_strings['LBL_LIST_FORM_TITLE'];

                    $group_panel = <<<EOQ
<div class="edit view edit508 " id="detailpanel_securitygroups">
    <h4>&nbsp;&nbsp;
    $lbl_securitygroups_select
    </h4>
    <table width="100%" cellspacing="1" cellpadding="0" border="0" class="edit view panelContainer" id="LBL_PANEL_SECURITYGROUPS">
    <tbody><tr>
    <td width="12.5%" valign="top" scope="col" id="account_type_label">
        $lbl_securitygroups:
    </td>
    <td width="37.5%" valign="top">
        <select title="" id="securitygroup_list" name="securitygroup_list[]" multiple="multiple" size="${group_count}">
        $group_options
        </select>
    </td>
    </tr>
    </tbody></table>
</div>
EOQ;
                    $group_panel = preg_replace("/[\r\n]+/", "", $group_panel);

                    $group_panel_append = <<<EOQ
<script>
    $('#${form_name}_tabs div:first').append($('${group_panel}'));
</script>
EOQ;
                    $str .= $group_panel_append;
                }
            }
        }
        /* END - SECURITY GROUPS */

        return $str;
    }

    protected function getTemplateHandler()
    {
        return new CGX_AdvancedView_TemplateHandler();
    }

}
