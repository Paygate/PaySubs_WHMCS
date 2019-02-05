<?php
/*
 * Copyright (c) 2019 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 * 
 */
use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';
$gatewaymodule = 'paysubslink';
$GATEWAY       = getGatewayVariables( $gatewaymodule );
if ( !$GATEWAY['type'] ) {
    die( 'Module Not Activated' );
}

$hash = '';

foreach ( $_POST as $k => $v ) {
    if ( $k == 'Hash' ) {
        break;
    }
    $hash .= $v;
}

$hash .= $GATEWAY['secret'];
$hash = md5( $hash );
$url  = '../../../clientarea.php?action=invoices';

if ( strtoupper( $hash ) != strtoupper( $_REQUEST['Hash'] ) ) {
    logTransaction( $GATEWAY['name'], $_POST, 'Invalid hash' );
    $paymentSuccess = false;
}
if (  ( $_REQUEST['p12'] == '00' ) || ( $_REQUEST['p12'] == '00' ) ) {
    // APPROVED
    if ( $_REQUEST['p4'] == 'Duplicate' ) {
        $paymentSuccess = false;
    } else {
        $invoicenum   = $_REQUEST['p2'];
        $invoiceId    = $_REQUEST['m_5'];
        $clientId     = $_REQUEST['m_6'];
        $serviceId    = $_REQUEST['m_7'];
        $subscription = ( isset( $_POST['RecurReference'] ) && $_POST['RecurReference'] ) ? true : false;
        if ( !$subscription ) {
            addInvoicePayment( $invoiceId, $_REQUEST['Uti'], $_REQUEST['p6'], 0, $gatewaymodule );
            logTransaction( $GATEWAY['name'], $_POST, 'Successful' );
            $paymentSuccess = true;
        } else {
            $invoice = Capsule::table( 'tblinvoices' )->join( 'tblinvoiceitems', 'tblinvoiceitems.invoiceid', '=', 'tblinvoices.id' )->where( 'tblinvoiceitems.relid', $serviceId )->where( 'tblinvoiceitems.type', '=', 'Hosting' )->where( 'tblinvoices.status', 'Unpaid' )->first();
            addInvoicePayment( $invoice->invoiceid, $_REQUEST['Uti'], $_REQUEST['p6'], 0, $gatewaymodule );
            logTransaction( $GATEWAY['name'], $_POST, 'Successful' );
            Capsule::table( 'tblhosting' )->where( 'id', $serviceId )->update( ['subscriptionid' => $_POST['RecurReference']] );
            $paymentSuccess = true;
        }
    }
} else {
    logTransaction( $GATEWAY['name'], $_POST, $_REQUEST['p3'] );
    // Hosting Cancel Status
    Illuminate\Database\Capsule\Manager::table( 'tblhosting' )
        ->where( 'orderid', $_POST['p3'] )
        ->update(
            [
                'domainstatus' => 'Cancelled',
            ]
        );
    // Orders Cancel Status
    Illuminate\Database\Capsule\Manager::table( 'tblorders' )
        ->where( 'invoiceid', $_REQUEST['m_5'] )
        ->update(
            [
                'status' => 'Cancelled',
            ]
        );
    $paymentSuccess = false;
}

callback3DSecureRedirect( $invoiceId, $paymentSuccess );
