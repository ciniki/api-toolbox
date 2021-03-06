<?php
//
// Description
// -----------
// This function will fetch the next row which has unresolved matches,
// from the ciniki_toolbox_excel_matches table.  
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
// last_row:            (optional) This argument can be specified to walk the matches without changing anything.
// 
// Returns
// -------
// <matches>
//      <match row1="44" col1="19" row2="45" col2="19" />
//      <match row1="44" col1="4" row2="45" col2="4" />
//      <match row1="44" col1="5" row2="45" col2="5" />
// </matches>
// <rows>
//      <row id="44">
//          <cells>
//              <cell row="44" col="1" data="Firstname" />
//              <cell row="44" col="2" data="Middlename" />
//              <cell row="44" col="3" data="Lastname" />
//              <cell row="44" col="4" data="Suffix" />
//          </cells>
//      </row>
// </rows>
//
function ciniki_toolbox_excelNextMatch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'excel_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Spreadsheet'), 
        'last_row'=>array('required'=>'no', 'default'=>'0', 'blank'=>'no', 'name'=>'Last Row'), 
        'status'=>array('required'=>'no', 'default'=>'', 'blank'=>'Yes', 'name'=>'Status'),
        'direction'=>array('required'=>'no', 'default'=>'fwd', 'blank'=>'Yes', 'name'=>'Status'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'toolbox', 'private', 'checkAccess');
    $ac = ciniki_toolbox_checkAccess($ciniki, $args['tnid'], 'ciniki.toolbox.excelNextMatch', $args['excel_id']);
    if( $ac['stat'] != 'ok' ) {
        return $ac;
    }

    //
    // Load the match information
    //
    $strsql = "SELECT row1, col1, row2, col2, match_status, match_result "
        . "FROM ciniki_toolbox_excel_matches "
        . "WHERE excel_id = '" . ciniki_core_dbQuote($ciniki, $args['excel_id']) . "' ";
    if( $args['status'] != 'any' ) {
        $strsql .= "AND match_result < 10 ";
    }
    $strsql .= "AND row1 = (";

    if( $args['direction'] == 'rev' ) {
        $strsql .= "SELECT MAX(row1) FROM ciniki_toolbox_excel_matches ";
    } else {
        $strsql .= "SELECT MIN(row1) FROM ciniki_toolbox_excel_matches ";
    }
    $strsql .= "WHERE excel_id = '" . ciniki_core_dbQuote($ciniki, $args['excel_id']) . "' ";
    if( $args['status'] != 'any' ) {
        $strsql .= "AND match_status = 1";
    }
    if( $args['last_row'] >= 0 ) {
        if( $args['direction'] == 'rev' ) {
            $strsql .= " AND row1 < '" . ciniki_core_dbQuote($ciniki, $args['last_row']) . "' ";
        } else {
            $strsql .= " AND row1 > '" . ciniki_core_dbQuote($ciniki, $args['last_row']) . "' ";
        }
    }
    $strsql .= ")";
    $rc = ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.toolbox', 'matches', 'match', array('stat'=>'fail', 'err'=>array('code'=>'ciniki.toolbox.17', 'msg'=>'No matches found.')));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $matches = $rc['matches'];

    //
    // Get row data to go along with the rows found in the matches
    //

    //
    // Build a associative array to create an index of unique rows to fetch
    //
    $rows = array();
    foreach($matches as $match_num => $match) {
        $rows[$match['match']['row1']] = 1;
        $rows[$match['match']['row2']] = 1;
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery2');
    $strsql = "SELECT row, col, data, status FROM ciniki_toolbox_excel_data "
        . "WHERE excel_id = '" . ciniki_core_dbQuote($ciniki, $args['excel_id']) . "' "
        . "AND row IN (" . ciniki_core_dbQuoteIDs($ciniki, array_keys($rows)) . ") "
        . "ORDER BY row, col ";
    $rc = ciniki_core_dbHashIDQuery2($ciniki, $strsql, 'ciniki.toolbox', 'rows', 'row', 'cells', 'cell');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rows = $rc['rows'];
    
    return array('stat'=>'ok', 'matches'=>$matches, 'rows'=>$rows);
}
?>
