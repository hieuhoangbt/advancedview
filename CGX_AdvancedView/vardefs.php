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
$dictionary['CGX_AdvancedView'] = array(
    'table' => 'cgx_advancedview',
    'audited' => true,
    'inline_edit' => true,
    'duplicate_merge' => true,
    'fields' => array(
        'group_folder' => array(
            'name' => 'group_folder',
            'vname' => 'LBL_GROUP_FOLDER',
            'type' => 'varchar',
            'len' => '255'
        ),
        'group_name' => array(
            'name' => 'group_name',
            'vname' => 'LBL_GROUP_NAME',
            'type' => 'varchar',
            'len' => '255'
        ),
        'role_list_enabled' => array(
            'name' => 'role_list_enabled',
            'vname' => 'LBL_ROLE_LIST_ENABLED',
            'type' => 'text'
        ),
        'weight' => array(
            'name' => 'weight',
            'vname' => 'LBL_WEIGHT',
            'type' => 'int'
        ),
        'customer_implementation' => array(
            'name' => 'customer_implementation',
            'vname' => 'LBL_CUSTOMER_IMPL',
            'type' => 'bool'
        )
    ),
    'relationships' => array(),
    'optimistic_locking' => true,
    'unified_search' => true
);
if (! class_exists('VardefManager')) {
    require_once ('include/SugarObjects/VardefManager.php');
}
VardefManager::createVardef('CGX_AdvancedView', 'CGX_AdvancedView', array(
    'basic',
    'assignable',
    'security_groups'
));
