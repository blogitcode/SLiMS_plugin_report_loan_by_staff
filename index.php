<?php
/**
 * @Created by          : BlogITCode
 * @Date                : 15/06/2022
 * @File name           : index.php
 * Author: blogitcode
 * Author URI: https://blogitcode.com
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 */


defined('INDEX_AUTH') OR die('Direct access not allowed!');
// IP based access limitation
require LIB.'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-circulation');
// start the session
require SB.'admin/default/session.inc.php';
require SB.'admin/default/session_check.inc.php';

// privileges checking
$can_read = utility::havePrivilege('circulation', 'r') || utility::havePrivilege('reporting', 'r');
$can_write = utility::havePrivilege('circulation', 'w') || utility::havePrivilege('reporting', 'w');

if (!$can_read) {
    die('<div class="errorBox">'.__('You don\'t have enough privileges to access this area!').'</div>');
}
require SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO.'simbio_GUI/form_maker/simbio_form_element.inc.php';
require SIMBIO.'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO.'simbio_DB/datagrid/simbio_dbgrid.inc.php';
require MDLBS.'reporting/report_dbgrid.inc.php';
/* Begin content*/
function httpQuery($query = [])
{
    return http_build_query(array_unique(array_merge($_GET, $query)));
}
$membershipTypes = membershipApi::getMembershipType($dbs);
$page_title = 'Loan History Report';
$reportView = false;
$num_recs_show = 50;
if (isset($_GET['reportView'])) {
    $reportView = true;
}

if (!$reportView) {
?>
    <!-- filter -->
    <div class="per_title">
    	<h2><?php echo __('Loan statistic by staff'); ?></h2>
	  </div>
    <div class="infoBox">
    <?php echo __('Report Filter'); ?>
    </div>
    <div class="sub_section">
    <form method="get" action="<?php echo $_SERVER['PHP_SELF'] . '?' . httpQuery(); ?>" target="reportView">
    <div id="filterForm">
	    <div class="form-group divRow">
                    <label><?php echo __('Loans by staff'); ?></label>
                    <?php
                    $utype_q = $dbs->query('SELECT user_id, realname FROM user');
                    $utype_options = array();
                    $utype_options[] = array('0', __('ALL'));
                    while ($utype_d = $utype_q->fetch_row()) {
                        $utype_options[] = array($utype_d[0], $utype_d[1]);
                    }
                    echo simbio_form_element::selectList('user_type', $utype_options,'','class="form-control col-2"');
                    ?>
          </div>
	
        <div class="form-group divRow">
            <label><?php echo __('Loan Date From'); ?></label>
            <?php
            echo simbio_form_element::dateField('startDate', '2000-01-01','class="form-control"');
            ?>
        </div>
        <div class="form-group divRow">
            <label><?php echo __('Loan Date Until'); ?></label>
            <?php
            echo simbio_form_element::dateField('untilDate', date('Y-m-d'),'class="form-control"');
            ?>
        </div>
        <div class="form-group divRow">
            <label><?php echo __('Loan Status'); ?></label>
            <select name="loanStatus" class="form-control col-2"><option value="ALL"><?php echo __('ALL'); ?></option><option value="0"><?php echo __('On Loan'); ?></option><option value="1"><?php echo __('Returned'); ?></option></select>
        </div>
		<div class="form-group divRow">
            <label><?php echo __('Membership Type'); ?></label>
            <select name="membershipType" class="form-control col-3">
            <?php 
            foreach ($membershipTypes as $key => $membershipType) {
            echo '<option value="'.$membershipType['member_type_name'].'">'.$membershipType['member_type_name'].'</option>';
            }
            ?>
            </select>
        </div>
        <div class="form-group divRow">
            <label><?php echo __('Location'); ?></label>
            <?php
            $loc_q = $dbs->query('SELECT location_id, location_name FROM mst_location');
            $loc_options = array();
            $loc_options[] = array('', __('ALL'));
            while ($loc_d = $loc_q->fetch_row()) {
                $loc_options[] = array($loc_d[1], $loc_d[1]);
            }
            echo simbio_form_element::selectList('location', $loc_options,'','class="form-control col-3"');
            ?>
        </div>	    
        <div class="form-group divRow">
            <label><?php echo __('Record each page'); ?></label>
            <input type="text" name="recsEachPage" size="3" maxlength="3" class="form-control col-1" value="<?php echo $num_recs_show; ?>" />
            <small class="text-muted"><?php echo __('Set between 20 and 200'); ?></small>
        </div>
    </div>
    <input type="button" class="s-btn btn btn-default" name="moreFilter" value="<?php echo __('Show More Filter Options'); ?>" />
    <input type="submit" class="s-btn btn btn-primary" name="applyFilter" value="<?php echo __('Apply Filter'); ?>" />
    <input type="hidden" name="reportView" value="true" />
	<input type="hidden" name="id" value="<?= $_GET['id'] ?>"/>
    <input type="hidden" name="mod" value="<?= $_GET['mod'] ?>"/>
    </form>
	</div>
    <!-- filter end -->
    <div class="paging-area"><div class="pt-3 pr-3" id="pagingBox"></div></div>
    <iframe name="reportView" id="reportView" src="<?php echo $_SERVER['PHP_SELF'].'?' . httpQuery(['reportView' => 'yes']); ?>" frameborder="0" style="width: 100%; height: 500px;"></iframe>
<?php
} else {
    ob_start();
    // table spec
    $table_spec = 'loan_history';

    // create datagrid
    $reportgrid = new report_datagrid();
    $reportgrid->table_attr = 'class="s-table table table-sm table-bordered"';

    $reportgrid->setSQLColumn('member_id AS \''.__('Member ID').'\'',
        'member_name AS \''.__('Member Name').'\'',
        'member_type_name AS \''.__('Membership Type').'\'',
        'item_code AS \''.__('Item Code').'\'',
        'title AS \''.__('Title').'\'',
        'loan_date AS \''.__('Loan Date').'\'',
        'due_date AS \''.__('Due Date').'\'', 'is_return AS \''.__('Loan Status').'\'');
    $reportgrid->setSQLorder('loan_date DESC');

    $criteria = 'member_id IS NOT NULL ';
    if (isset($_GET['id_name']) AND !empty($_GET['id_name'])) {
        $id_name = utility::filterData('id_name', 'get', true, true, true);
        $criteria .= ' AND (member_id LIKE \'%'.$id_name.'%\' OR member_name LIKE \'%'.$id_name.'%\')';
    }
    if (isset($_GET['title']) AND !empty($_GET['title'])) {
        $keyword = utility::filterData('title', 'get', true, true, true);
        $words = explode(' ', $keyword);
        if (count($words) > 1) {
            $concat_sql = ' AND (';
            foreach ($words as $word) {
                $concat_sql .= " (title LIKE '%$word%') AND";
            }
            // remove the last AND
            $concat_sql = substr_replace($concat_sql, '', -3);
            $concat_sql .= ') ';
            $criteria .= $concat_sql;
        } else {
            $criteria .= ' AND title LIKE \'%'.$keyword.'%\'';
        }
    }
    if (isset($_GET['user_type']) AND !empty($_GET['user_type'])) {
        $user_type = utility::filterData('user_type', 'get', true, true, true);
        $criteria .= ' AND uid=\''.$user_type.'\'';
    }
    // loan date
    if (isset($_GET['startDate']) AND isset($_GET['untilDate'])) {
        $criteria .= ' AND (TO_DAYS(loan_date) BETWEEN TO_DAYS(\''.utility::filterData('startDate', 'get', true, true, true).'\') AND
            TO_DAYS(\''.utility::filterData('untilDate', 'get', true, true, true).'\'))';
    }
    // loan status
    if (isset($_GET['loanStatus']) AND $_GET['loanStatus'] != 'ALL') {
        $loanStatus = (integer)utility::filterData('loanStatus', 'get', true, true, true);
        $criteria .= ' AND is_return='.$loanStatus;
    }

    if ((isset($_GET['membershipType'])) AND ($_GET['membershipType'] != 'All')) {
        $membershipType = utility::filterData('membershipType', 'get', true, true, true);
        $criteria .= ' AND member_type_name LIKE \''.$membershipType.'\'';
    }else{
        $criteria .= ' AND member_type_name LIKE \'%%\'';
    }
	
    // item location	
    if (isset($_GET['location']) AND !empty($_GET['location'])) {
        $location = utility::filterData('location', 'get', true, true, true);
        $criteria .= ' AND location_name LIKE \''.$location.'\'';
    }
	
    if (isset($_GET['recsEachPage'])) {
        $recsEachPage = (integer)utility::filterData('recsEachPage', 'get', true, true, true);
        $num_recs_show = ($recsEachPage >= 20 && $recsEachPage <= 200)?$recsEachPage:$num_recs_show;
    }
    $reportgrid->setSQLCriteria($criteria);

   // callback function to show loan status
    function loanStatus($obj_db, $array_data)
    {
        if ($array_data[7] == 0) {
            return '<strong>'.__('On Loan').'</strong>';
        } else {
            return __('Returned');
        }
    }

    // modify column value
    $reportgrid->modifyColumnContent(7, 'callback{loanStatus}');

    // put the result into variables
    echo $reportgrid->createDataGrid($dbs, $table_spec, $num_recs_show);

    echo '<script type="text/javascript">'."\n";
    echo 'parent.$(\'#pagingBox\').html(\''.str_replace(array("\n", "\r", "\t"), '', $reportgrid->paging_set).'\');'."\n";
    echo '</script>';

    $content = ob_get_clean();
    // include the page template
    require SB.'/admin/'.$sysconf['admin_template']['dir'].'/printed_page_tpl.php';
}
/* Content end */
?>
