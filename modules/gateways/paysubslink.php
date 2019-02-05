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

function paysubslink_config()
{
    $configarray = [
        "FriendlyName" => ["Type" => "System", "Value" => "PaySubs"],
        "merchantid"   => ["FriendlyName" => "Terminal ID", "Type" => "text", "Size" => "20"],
        "secret"       => ["FriendlyName" => "Secret", "Type" => "password", "Size" => "30"],
        "budget"       => array( "FriendlyName" => "Accept PaySubs Budget Payments", "Type" => "yesno" ),
        "recurring"    => array( "FriendlyName" => "Enable Recurring", "Type" => "yesno" ),
    ];
    return $configarray;
}

function paysubslink_link( $params )
{
    $invoice           = Capsule::table( 'tblinvoices' )->join( 'tblinvoiceitems', 'tblinvoiceitems.invoiceid', '=', 'tblinvoices.id' )->where( 'tblinvoices.id', $params['invoiceid'] )->first();
    $invoiceNumber     = 'Invoice ' . $params['invoicenum'];
    $description       = $params["description"];
    $amount            = $params['amount']; # Format: ##.##
    $currency          = $params['currency']; # Currency Code
    $companyname       = $params['companyname'];
    $systemurl         = $params['systemurl'];
    $currency          = $params['currency'];
    $gatewaymerchantid = $params['merchantid'];
    $gatewaybudget     = $params['budget'];
    $gatewayrecurring  = $params['recurring'];
    $customerID        = $params['clientdetails']['userid'];
    $serviceId         = $invoice->relid;
    $invoiceId         = $params['invoiceid'];
    if ( $gatewaybudget == 1 || $gatewaybudget == 'on' ) {
        $gatewaybudget = 'Y';
    } else {
        $gatewaybudget = 'N';
    }
    if ( $gatewayrecurring == 1 || $gatewayrecurring == 'on' ) {
        $gatewayrecurring = true;
    } else {
        $gatewayrecurring = false;
    }
    $return_url  = $params['systemurl'] . 'modules/gateways/callback/paysubslink.php';
    $cancelUrl   = $params['returnurl'];
    $hash        = $gatewaymerchantid . $invoiceNumber . $params['description'] . $amount . $params['currency'] . $cancelUrl . $gatewaybudget . $email . $invoiceId . $customerID . $serviceId . $params['secret'];
    $hash        = md5( $hash );

    $invoices     = Capsule::table( 'tblinvoices' )->join( 'tblinvoiceitems', 'tblinvoiceitems.invoiceid', '=', 'tblinvoices.id' )->where( 'tblinvoices.id', $params['invoiceid'] )->get();
    $subscription = '';
    foreach ( $invoices as $invoice ) {
        if ( !$invoice->type ) {
        } elseif ( $invoice->type == 'Hosting' ) {
            $service = Capsule::table( 'tblhosting' )->where( 'id', $invoice->relid )->first();
            switch ( $service->billingcycle ) {
                case 'Daily':
                    $paymentInterval = 'D';
                    break;
                case 'Weekly':
                    $paymentInterval = 'W';
                    break;
                case 'Monthly':
                    $paymentInterval = 'M';
                    break;
                case 'Quarterly':
                    $paymentInterval = 'Q';
                    break;
                case 'Semi-Annually':
                    $paymentInterval = '6';
                    break;
                case 'Annually':
                    $paymentInterval = 'Y';
                    break;
                default:
                    $paymentInterval = 0;
            }
            if ( $subscription && ( $subscription != $paymentInterval ) ) {
                $subscription = '';
                break;
            }
            $subscription = $paymentInterval;
        } else {
            $service = Capsule::table( 'tbldomains' )->where( 'id', $invoice->relid )->first();
            switch ( $domain->registrationperiod ) {
                case '1':
                    $paymentInterval = 'Y';
                    break;
                default:
                    $paymentInterval = 0;
            }
            if ( $subscription && ( $subscription != $paymentInterval ) ) {
                $subscription = '';
                break;
            } else {
                $subscription = $paymentInterval;
            }

        }
    }
    if ( !$subscription ) {
        $gatewayrecurring = false;
    }
    if ( !$gatewayrecurring ) {
        $html = '<form method="post" action="https://www.vcs.co.za/vvonline/vcspay.aspx">
                 <input type="hidden" name="p1" value="' . $gatewaymerchantid . '" />
                 <input type="hidden" name="p2" value="' . $invoiceNumber . '" />
                 <input type="hidden" name="p3" value="' . $description . '" />
                 <input type="hidden" name="p4" value="' . $amount . '" />
                 <input type="hidden" name="p5" value="' . $currency . '" />
                 <input type="hidden" name="p10" value="' . $cancelUrl . '" />
                 <input type="hidden" name="Budget" value="' . $gatewaybudget . '" />
                 <input type="hidden" name="CardHolderEmail" value="' . $email . '" />
                 <input type="hidden" name="m_5" value="' . $invoiceId . '" />
                 <input type="hidden" name="m_6" value="' . $customerID . '" />
                 <input type="hidden" name="m_7" value="' . $serviceId . '" />
                 <input type="hidden" name="m_8" value="" />
                 <input type="hidden" name="m_9" value="" />
                 <input type="hidden" name="m_10" value="" />
                 <input type="hidden" name="hash" value="' . $hash . '" />
                 <input type="hidden" name="UrlsProvided" value="Y" />
                 <input type="hidden" name="ApprovedUrl" value="' . $return_url . '" />
                 <input type="hidden" name="DeclinedUrl" value="' . $return_url . '" />
                 <input type="submit" class="btn btn-default" value="Pay (Once-Off)" />
            </form>';
    } else {
        $hash = $gatewaymerchantid . $invoiceNumber . $params['description'] . $amount . $params['currency'] . 'U' . $paymentInterval . $cancelUrl . $gatewaybudget . $email . $invoiceId . $customerID . $serviceId . $params['secret'];
        $hash = md5( $hash );
        $html = '<form  method="post" action="https://www.vcs.co.za/vvonline/vcspay.aspx">
                 <input type="hidden" name="p1" value="' . $gatewaymerchantid . '" />
                 <input type="hidden" name="p2" value="' . $invoiceNumber . '" />
                 <input type="hidden" name="p3" value="' . $description . '" />
                 <input type="hidden" name="p4" value="' . $amount . '" />
                 <input type="hidden" name="p5" value="' . $currency . '" />
                 <input type="hidden" name="p6" value="U" />
                 <input type="hidden" name="p7" value="' . $subscription . '" />
                 <input type="hidden" name="p10" value="' . $cancelUrl . '" />
                 <input type="hidden" name="Budget" value="' . $gatewaybudget . '" />
                 <input type="hidden" name="CardHolderEmail" value="' . $email . '" />
                 <input type="hidden" name="m_5" value="' . $invoiceId . '" />
                 <input type="hidden" name="m_6" value="' . $customerID . '" />
                 <input type="hidden" name="m_7" value="' . $serviceId . '" />
                 <input type="hidden" name="m_8" value="" />
                 <input type="hidden" name="m_9" value="" />
                 <input type="hidden" name="m_10" value="" />
                 <input type="hidden" name="hash" value="' . $hash . '" />
                 <input type="hidden" name="UrlsProvided" value="Y" />
                 <input type="hidden" name="ApprovedUrl" value="' . $return_url . '" />
                 <input type="hidden" name="DeclinedUrl" value="' . $return_url . '" />
                 <input type="submit" class="btn btn-default" value="Pay (Recurring)" />
            </form>';
    }
    return $html;
}
