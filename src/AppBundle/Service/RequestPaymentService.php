<?php
/**
 * The service to request payment from developer's customer.
 *
 * Created by samuel.
 * Date: 26/09/2016
 * Time: 10:10
 */

namespace AppBundle\Service;


class RequestPaymentService
{
    /**
     * RequestPaymentService constructor.
     */
    public function __construct()
    {
    }

    public function requestPayment($amount, $cusometPhoneNmber){
        // Authentication parameters of MTN MoMo account
       $spId ="SPID goes here";
        $spPassword ="spPassword goes here";
        $serviceId ="ServiceId goes here";
        $timeStamp = date("YmdHis");
        $crypt_pass= md5($spId.$spPassword.$timeStamp);

        // Generate processing number: Start
        $ProcessingNumber = time(); // Generated processing number.
        // Generate processing number: End

        $Amount = $amount;
        $MSISDNNum = '237'. $phoneNumber;
        $OpCoID="23701";
        $OrderDateTime="20160523";
        $appVersion="1.7";
        $PrefLang="en";
        $Narration= "Not configured developer's narration";   

        $send = $this->createRequestXML(array(
            'spId' => $spId,
            'spPassword' => $crypt_pass,
            'timeStamp' => $timeStamp,
            'serviceId' => $serviceId,
            'Amount' => $Amount,
            'MSISDNNum' => $MSISDNNum,
            'ProcessingNumber' => $ProcessingNumber,
            'OpCoID' => $OpCoID,
            'AcctBalance' => $AcctBalance,
            'AcctRef' => $AcctRef,
            'MinDueAmount' => $MinDueAmount,
            'Narration' => $Narration,
            'PrefLang' => $PrefLang
        ));

        $headers = [
            "Accept-Encoding: gzip,deflate",
            "Content-Type: text/xml;charset=UTF-8",
            "SOAPAction: POST"
        ];

        $URL = 'https://41.206.4.162:8443/ThirdPartyServiceUMMImpl/UMMServiceService/RequestPayment/v17';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_TIMEOUT, 150);
        curl_setopt( $ch, CURLOPT_URL, $URL );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $send );
        $result = curl_exec($ch); //

        //echo "request payment ".$result;

        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);

        header('Content-type: application/xml');
        if ($curl_errno > 0) {
            return '<?xml version="1.0" encoding="utf-8" ?><result><error>Yes</error><code>' . $curl_errno . '</code><message>' . $curl_error . '</message></result>';
        } else {
            curl_close($ch);
            return $result;
        }
        // get ConfirmThirdPartyPayment request information from SDP
        $data = (file_get_contents("php://input")) ? file_get_contents("php://input") : '';

        $result_ThirdPartySDPRequest = $data;

        $result_ThirdPartySDPRequest = str_replace("\n","",$result_ThirdPartySDPRequest);
        $result_ThirdPartySDPRequest= str_replace("<soapenv:Header>","",$result_ThirdPartySDPRequest);
        $result_ThirdPartySDPRequest = str_replace("</soapenv:Header>","",$result_ThirdPartySDPRequest);
        $result_ThirdPartySDPRequest = str_replace("<soapenv:Body>","",$result_ThirdPartySDPRequest);
        $result_ThirdPartySDPRequest = str_replace("</soapenv:Body>","",$result_ThirdPartySDPRequest);

        $result_ThirdPartySDPRequest = str_replace("ns1:traceUniqueID","traceUniqueID",$result_ThirdPartySDPRequest);
        $result_ThirdPartySDPRequest = str_replace(":NotifySOAPHeader","",$result_ThirdPartySDPRequest);
        $result_ThirdPartySDPRequest = str_replace(":processRequest","",$result_ThirdPartySDPRequest);

        $dat = explode("phoneNumber", $result_ThirdPartySDPRequest);
        $response = simplexml_load_string(html_entity_decode($dat[0]), 'SimpleXMLElement', LIBXML_NOCDATA);

        //get parameters from ThirdPartyRequestXML
//        echo $traceUniqueID = $response->ns1->traceUniqueID;
        $traceUniqueID = $response->ns1->traceUniqueID;
        $serviceId = $response->ns2->serviceId;
        $ProcessingNumber = $response->ns2->parameter[0]->value;
        $senderID = $response->ns2->parameter[1]->value;
        $AcctRef = $response->ns2->parameter[2]->value;
        $RequestAmount = $response->ns2->parameter[3]->value;
        $paymentRef = $response->ns2->parameter[4]->value;
        $ThirdPartyTransactionID = $response->ns2->parameter[5]->value;
        $MOMAcctNum = $response->ns2->parameter[6]->value;
        $CustName = $response->ns2->parameter[7]->value;
        $StatusCode = $response->ns2->parameter[8]->value;
        $TXNType = $response->ns2->parameter[9]->value;
        $OpCoID = $response->ns2->parameter[10]->value;

        $send_createConfirmationXML = $this->createConfirmationXML(array(
            'ProcessingNumber' => $ProcessingNumber,
            'StatusCode' => $StatusCode,
            'StatusDesc' => $StatusDesc,
            'ThirdPartyAcctRef' => $ThirdPartyAcctRef,
            'Token' => $Token,
        ));

        $ch_ThirdParty = curl_init();
        curl_setopt($ch_ThirdParty, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch_ThirdParty, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt( $ch_ThirdParty, CURLOPT_URL, $URL );
        curl_setopt( $ch_ThirdParty, CURLOPT_POST, true );
        curl_setopt( $ch_ThirdParty, CURLOPT_HTTPHEADER, $headers);
        curl_setopt( $ch_ThirdParty, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch_ThirdParty, CURLOPT_POSTFIELDS, $send_createConfirmationXML );
        $result_ThirdParty = curl_exec($ch_ThirdParty);

        $curl_errnoConfirm = curl_errno($ch_ThirdParty);
        $curl_errorConfirm = curl_error($ch_ThirdParty);

        if ($curl_errnoConfirm > 0) {
            return '<?xml version="1.0" encoding="utf-8" ?><result><error>Yes</error><code>' . $curl_errnoConfirm . '</code><message>' . $curl_errorConfirm . '</message></result>';
        } else {
            curl_close($ch_ThirdParty);
        }
    }


    //Request for RequestPayment
    function createRequestXML($params = array()) { // for Request for RequestPayment
        $spId = (array_key_exists('spId',$params)) ? $params['spId'] : "2370110000468"; // required
        $spPassword = (array_key_exists('spPassword',$params)) ? $params['spPassword'] : "7efe81b7bc1bae00fc2fbddebdb75aea"; // required
        $timeStamp = (array_key_exists('timeStamp',$params)) ? $params['timeStamp'] : "20150306141400"; // not required
        $serviceId  = (array_key_exists('serviceId',$params)) ? $params['serviceId'] : "ECASH"; //  required
        $Amount = (array_key_exists('Amount',$params)) ? $params['Amount'] : '750'; // not required
        $MSISDNNum = (array_key_exists('MSISDNNum',$params)) ? $params['MSISDNNum'] : "237670992439"; // not required
        $ProcessingNumber = (array_key_exists('ProcessingNumber',$params)) ? $params['ProcessingNumber'] : "4651916368992208118911730586890"; // not required
        $OpCoID = (array_key_exists('OpCoID',$params)) ? $params['OpCoID'] : "23701"; // not required
        $AcctBalance = (array_key_exists('AcctBalance',$params)) ? $params['AcctBalance'] : "300"; // not required
        $AcctRef = (array_key_exists('AcctRef',$params)) ? $params['AcctRef'] : "merleauponti@yahoo.fr"; // not required
        $MinDueAmount = (array_key_exists('MinDueAmount',$params)) ? $params['MinDueAmount'] : "300"; // not required
        $Narration = (array_key_exists('Narration',$params)) ? $params['Narration'] : "AA0068"; // not required
        $PrefLang = (array_key_exists('PrefLang',$params)) ? $params['PrefLang'] : "en"; // not required

        $xml='<?xml version="1.0" encoding="utf-8"?>
		   <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:b2b="http://b2b.mobilemoney.mtn.zm_v1.0">
		  <soapenv:Header>
			  <ns1:RequestSOAPHeader xmlns:ns1="http://www.huawei.com.cn/schema/common/v2_1">
				  <ns1:spId>'.$spId.'</ns1:spId>
				  <ns1:spPassword>'.$spPassword.'</ns1:spPassword>
				  <ns1:serviceId></ns1:serviceId>
				  <ns1:timeStamp>'.$timeStamp.'</ns1:timeStamp>
			  </ns1:RequestSOAPHeader>
		  </soapenv:Header>
		  <soapenv:Body>
			  <ns1:processRequest xmlns:ns1="http://b2b.mobilemoney.mtn.zm_v1.0/">
				  <serviceId xmlns="">'.$serviceId.'</serviceId>
				  <parameter xmlns="">
					  <name>DueAmount</name>
					  <value>'.$Amount.'</value>
				  </parameter>
				  <parameter xmlns="">
					  <name>MSISDNNum</name>
					  <value>'.$MSISDNNum.'</value>
				  </parameter>
				  <parameter xmlns="">
					  <name>ProcessingNumber</name>
					  <value>'.$ProcessingNumber.'</value>
				  </parameter>
				  
				  <parameter xmlns="">
					  <name>AcctRef</name>
					  <value>'.$AcctRef.'</value>
				  </parameter>
				  <parameter xmlns="">
					  <name>OpCoID</name>
					  <value>'.$OpCoID.'</value>
				  </parameter>
				  <parameter> 
				      <name>AcctBalance</name> 
					  <value>'.$AcctBalance.'</value> 
				  </parameter> 
				  <parameter> 
				      <name>MinDueAmount</name> 
					  <value>'.$MinDueAmount.'</value> 
				  </parameter> 
				  <parameter xmlns="">
					  <name>Narration</name>
					  <value>'.$Narration.'</value>
				  </parameter>
				  <parameter xmlns="">
					  <name>PrefLang</name>
					  <value>'.$PrefLang.'</value>
				  </parameter>
			  </ns1:processRequest>
		  </soapenv:Body>
	  </soapenv:Envelope>';
        return str_replace("\n","",$xml);
    }

    // sending Response to ConfirmThirdPartyPayment

    function createConfirmationXML($params = array()) {  // creating resquest for send ConfirmThirdPartyPayment response
        $ProcessingNumber = (array_key_exists('ProcessingNumber',$params)) ? $params['ProcessingNumber'] : "4651916368992208118911730586890"; // required
        $StatusCode = (array_key_exists('StatusCode',$params)) ? $params['StatusCode'] : "121212"; // required
        $StatusDesc = (array_key_exists('StatusDesc',$params)) ? $params['StatusDesc'] : "5131"; // not required
        $ThirdPartyAcctRef = (array_key_exists('ThirdPartyAcctRef',$params)) ? $params['ThirdPartyAcctRef'] : "5131"; //required
        $Token = (array_key_exists('Token',$params)) ? $params['Token'] : "5131"; //required

        $xml='<?xml version="1.0" encoding="utf-8"?>
			<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:b2b="http://b2b.mobilemoney.mtn.zm_v1.0/">
			<soapenv:Header/> 
					<soapenv:Body> 
							<b2b:processRequestResponse> 
									<return> 
											<name>ProcessingNumber</name> 
											<value>'.$ProcessingNumber.'</value> 
									</return> 
									<return> 
											<name>StatusCode</name> 
											<value>'.$StatusCode.'</value> 
									</return> 
									<return> 
											<name>StatusDesc</name> 
											<value>'.$StatusDesc.'</value> 
									</return> 
									<return> 
											<name>ThirdPartyAcctRef</name> 
											<value>'.$ThirdPartyAcctRef.'</value> 
									</return> 
									<return> 
											<name>Token</name> 
											<value>'.$Token.'</value> 
									</return> 
							</b2b:processRequestResponse> 
					</soapenv:Body> 
					</soapenv:Envelope>';
        return str_replace("\n","",$xml);
    }

}
