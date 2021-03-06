<?php
//
// Description
// -----------
// This function will remove all entries for an excel file.  
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
// 
// Returns
// -------
// <rsp stat="ok" />
//
function ciniki_toolbox_excelDelete($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'excel_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Spreadsheet'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'toolbox', 'private', 'checkAccess');
    $ac = ciniki_toolbox_checkAccess($ciniki, $args['tnid'], 'ciniki.toolbox.excelDelete', $args['excel_id']);
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Turn off autocommit
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.toolbox');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Delete the file from the list
    //
    $strsql = "DELETE FROM ciniki_toolbox_excel "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['excel_id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.toolbox');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.toolbox');
        return $rc;
    }

    //
    // Delete all data for a row
    //
    $strsql = "DELETE FROM ciniki_toolbox_excel_data "
        . "WHERE excel_id = '" . ciniki_core_dbQuote($ciniki, $args['excel_id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.toolbox');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.toolbox');
        return $rc;
    }

    //
    // Delete all matches for a file
    //
    $strsql = "DELETE FROM ciniki_toolbox_excel_matches "
        . "WHERE excel_id = '" . ciniki_core_dbQuote($ciniki, $args['excel_id']) . "' "
        . "";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.toolbox');
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
