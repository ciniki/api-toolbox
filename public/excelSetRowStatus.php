<?php
//
// Description
// -----------
// This function will mark a row deleted in the excel data, but will not remove it from
// the list of matches.  
//
// Info
// ----
// Status:              alpha
//
// Arguments
// ---------
// api_key:
// auth_token:
// excel_id:            The excel spread ID that was uploaded to ciniki_toolbox_excels table.
// rows                 The row number to mark deleted in the ciniki_toolbox_excel_data table.
// 
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_toolbox_excelSetRowStatus($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'excel_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Spreadsheet'), 
        'row'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Row'), 
        'status'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Status'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'toolbox', 'private', 'checkAccess');
    $ac = ciniki_toolbox_checkAccess($ciniki, $args['tnid'], 'ciniki.toolbox.excelSetRowStatus', $args['excel_id']);
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }


    //
    // Turn off autocommit
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.toolbox');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Mark the row delete in the excel_data
    //
    if( $args['status'] == 'delete' ) {
        $strsql = "UPDATE ciniki_toolbox_excel_data SET status = 2 "
            . "WHERE excel_id = '" . ciniki_core_dbQuote($ciniki, $args['excel_id']) . "' "
            . "AND row = '" . ciniki_core_dbQuote($ciniki, $args['row']) . "'";
    } else if( $args['status'] == 'keep' ) {
        $strsql = "UPDATE ciniki_toolbox_excel_data SET status = 3 "
            . "WHERE excel_id = '" . ciniki_core_dbQuote($ciniki, $args['excel_id']) . "' "
            . "AND row = '" . ciniki_core_dbQuote($ciniki, $args['row']) . "'";
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.toolbox.19', 'msg'=>'Invalid status specified'));
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.toolbox');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.toolbox');
        return $rc;
    }

    //
    // Commit the changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.toolbox');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'toolbox');

    return array('stat'=>'ok');
}
?>
