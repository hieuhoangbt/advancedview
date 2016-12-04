
<script type="text/javascript" src="cache/include/javascript/sugar_grp_yui_widgets.js"></script>

<form name="ConfigureTabs" method="POST" action="index.php" id="conf_role">
    <input type="hidden" name="module" value="CGX_AdvancedView">
    <input type="hidden" name="module_setting" id="custom_module_settings" value="{$MODULE_SETTING}">
    <input type="hidden" name="action" value="makegroup">
    <input type="hidden" name="clone_group" value="{$CLONE_GROUP}">
    <input type="hidden" name="clone_group_folder" value="{$CLONE_GROUP_FOLDER}">
    <input type="hidden" id="role_list_enabled" name="role_list_enabled" value="">
    <input type="hidden" id="role_list_disabled" name="role_list_disabled" value="">
    <table border="0" cellspacing="1" cellpadding="1" class="actionsContainer">
        <tr>
            <td>
                <input title="{$APP.LBL_SAVE_BUTTON_TITLE}" class="button primary" onclick="SUGAR.saveConfigureTabs();this.form.action.value='makegroup'; " type="button" name="SubmitForm" value="{$APP.LBL_SAVE_BUTTON_LABEL}" >
                <input title="{$APP.LBL_CANCEL_BUTTON_TITLE}" class="button" onclick="ModuleBuilder.getContent('module=CGX_AdvancedView&action=wizard&view=layouts&view_module={$MODULE_SETTING}');" type="button" name="button" value="{$APP.LBL_CANCEL_BUTTON_LABEL}">
            </td>
        </tr>
    </table>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr><td colspan='100'>
                <br/>
                <div class='add_table' style='margin-bottom:5px'>
                    <table id="ConfigureTabs" class="themeSettings edit view" style='margin-bottom:0px;' border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td>
                                {$MOD.LBL_GROUP_NAME}
                            </td>
                            <td>
                                <input type = "text" name="group_name" id="group_name" style="width: 210px;" value="{$GROUP_NAME}">
                                <input type = "hidden" name="group_folder" id="group_folder" style="width: 210px;" value="{$GROUP_FOLDER}">
                            </td>
                        </tr>
                        <tr>
                            <td>{$MOD.LBL_CUSTOMER_IMPL}</td>
                            <td><input type="checkbox" name="customer_implementation" id="customer_implementation" value="{$CUSTOMER_IMPL}" {if $CUSTOMER_IMPL == 1}checked="checked" onclick="return false;"{/if} /> <label for="customer_implementation">This cannot be reverted.</label></td>
                        </tr>
                        <tr>
                            <td width='1%'>
                                <div id="enabled_div" class="enabled_tab_workarea">
                                </div>
                            </td>
                            <td>
                                <div id="disabled_div" class="disabled_tab_workarea">
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                
            </td></tr>
    </table>
    
</form>

<script type="text/javascript">
    var role_list_enabled = {$ROLE_LIST_ENABLED};
    var role_list_disabled = {$ROLE_LIST_DISABLED};
    var lblEnabled = '{sugar_translate label="LBL_ENABLED_ROLES"}';
    var lblDisabled = '{sugar_translate label="LBL_DISABLED_ROLES"}';
    {literal}

    SUGAR.enabledTabsTable = new YAHOO.SUGAR.DragDropTable(
            "enabled_div",
            [{key:"role_name",  label: lblEnabled, width: 200, sortable: false},
                {key:"role_id", label: lblEnabled, hidden:true}],
            new YAHOO.util.LocalDataSource(role_list_enabled, {
                responseSchema: {
                    resultsList : "roles",
                    fields : [{key : "role_id"}, {key : "role_name"}]
                }
            }),
            {
                height: "300px",
                group: ["enabled_div", "disabled_div"]
            }
    );
    SUGAR.disabledTabsTable = new YAHOO.SUGAR.DragDropTable(
            "disabled_div",
            [{key:"role_name",  label: lblDisabled, width: 200, sortable: false},
                {key:"role_id", label: lblDisabled, hidden:true}],
            new YAHOO.util.LocalDataSource(role_list_disabled, {
                responseSchema: {
                    resultsList : "roles",
                    fields : [{key : "role_id"}, {key : "role_name"}]
                }
            }),
            {
                height: "300px",
                group: ["enabled_div", "disabled_div"]
            }
    );
    SUGAR.enabledTabsTable.disableEmptyRows = true;
    SUGAR.disabledTabsTable.disableEmptyRows = true;
    SUGAR.enabledTabsTable.addRow({role_id: "", role_name: ""});
    SUGAR.disabledTabsTable.addRow({role_id: "", role_name: ""});
    SUGAR.enabledTabsTable.render();
    SUGAR.disabledTabsTable.render();
    SUGAR.saveConfigureTabs = function()
    {
        addToValidate('ConfigureTabs', 'group_name', true, 'Group name is Required');
        if(!check_form('ConfigureTabs')) return false;
        var enabledTable = SUGAR.enabledTabsTable;
        var roles = [];
        for(var i=0; i < enabledTable.getRecordSet().getLength(); i++){
            var data = enabledTable.getRecord(i).getData();
            if (data.role_id && data.role_id != '')
                roles[i] = data.role_id;
        }
        // Set enabled role data
        YAHOO.util.Dom.get('role_list_enabled').value = YAHOO.lang.JSON.stringify(roles);

        var disabledTable = SUGAR.disabledTabsTable;
        var roles = [];
        for(var i=0; i < disabledTable.getRecordSet().getLength(); i++){
            var data = disabledTable.getRecord(i).getData();
            if (data.role_id && data.role_id != '')
                roles[i] = data.role_id;
        }
        // Set disabled role data
        YAHOO.util.Dom.get('role_list_disabled').value = YAHOO.lang.JSON.stringify(roles);
        
        var ajaxSettings = {
            url: "index.php?to_pdf=1&sugar_body_only=1&module=CGX_AdvancedView&action=makegroup",
            method: "POST",
            data: $('#conf_role').serialize(),
            success: function(ajaxResponse){
                if(typeof ModuleBuilder != 'undefined'){
                    ajaxResponse = JSON.parse(ajaxResponse);
                    ModuleBuilder.cUpdateTree(ajaxResponse);
                    var mod_setting = $("#custom_module_settings").val();
                    var request = 'module=CGX_AdvancedView&action=wizard&view=groups&view_module='+mod_setting+'&group_name='+ajaxResponse.group_folder;
                    ModuleBuilder.getContent( request ) ;
                    YAHOO.SUGAR.MessageBox.hide();
                }else{
                    alert("ModuleBuilder is not defined!");
                }
            }
        };
        $.ajax(ajaxSettings);
        ajaxStatus.showStatus(SUGAR.language.get('CGX_AdvancedView', 'LBL_AJAX_SAVING_CONFIG'));
        YAHOO.SUGAR.MessageBox.show({
            title: SUGAR.language.get('CGX_AdvancedView', 'LBL_AJAX_SAVING_CONFIG'),
            msg: SUGAR.language.get('CGX_AdvancedView', 'LBL_AJAX_SAVING_CONFIG_TITLE'),
            width: 500,
            close: false
        });
    }
    {/literal}
</script>