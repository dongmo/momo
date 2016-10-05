<?php
/**
 * Service to send money to a particular number via our API.
 * Generaly we will use it to send money to developers who request payment from theirs customers.
 *
 * Created by samuel.
 * Date: 26/09/2016
 * Time: 10:13
 */

namespace AppBundle\Service;


class DepositMoneyService
{

    /**
     * DepositMoneyService constructor.
     */
    public function __construct()
    {
    }

    /**
     * Send an amount of money to a developer.
     *
     * @param $amount
     * @param $phoneNumber
     * @return mixed|string
     */
    public function sendMoney($amount, $phoneNumber){
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
            'Narration' => $Narration,
            'appVersion' => $appVersion,
            'PrefLang' => $PrefLang
        ));

        $URL = 'https://41.206.4.162:8443/ThirdPartyServiceUMMImpl/UMMServiceService/DepositMobileMoney/v17';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt( $ch, CURLOPT_URL, $URL );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $send );
        $result = curl_exec($ch);

        //echo "result deposit = ".$result;
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        header('Content-type: application/xml');

        if ($curl_errno > 0) {
            return '<?xml version="1.0" encoding="utf-8"?><result><error>Yes</error><code>$curl_errno</code><message>$curl_error</message></result>';
        } else {
            return $result;
        }

    }

    /**
     * Add required parameters(In SOAP) before make the MoMo request.
     *
     * @param array $params
     * @return mixed
     */
    public function createRequestXML($params = array()) {
        $spId = (array_key_exists('spId',$params)) ? $params['spId'] : "2370110002099"; // required
        $spPassword = (array_key_exists('spPassword',$params)) ? $params['spPassword'] : "7efe81b7bc1bae00fc2fbddebdb75aea"; // required
        $timeStamp = (array_key_exists('timeStamp',$params)) ? $params['timeStamp'] : "20150306141400"; // not required
        $serviceId  = (array_key_exists('serviceId',$params)) ? $params['serviceId'] : "ECASH"; //  required
        $Amount = (array_key_exists('Amount',$params)) ? $params['Amount'] : '300'; // not required
        $MSISDNNum = (array_key_exists('MSISDNNum',$params)) ? $params['MSISDNNum'] : "750"; // not required
        $ProcessingNumber = (array_key_exists('ProcessingNumber',$params)) ? $params['ProcessingNumber'] : "46519163689922081189117305868901"; // not required
        $OpCoID = (array_key_exists('OpCoID',$params)) ? $params['OpCoID'] : "23701"; // not required
        $Narration = (array_key_exists('Narration',$params)) ? $params['Narration'] : "AA0068"; // not required
        //$OrderDateTime = (array_key_exists('OrderDateTime',$params)) ? $params['OrderDateTime'] : "20150307"; // not required
        $appVersion = (array_key_exists('appVersion',$params)) ? $params['appVersion'] : "1.7"; // not required
        $PrefLang = (array_key_exists('PrefLang',$params)) ? $params['PrefLang'] : "en"; // not required

        $xml='<?xml version="1.0" encoding="utf-8"?>
		   <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
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
					  <name>Amount</name>
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
					  <name>OpCoID</name>
					  <value>'.$OpCoID.'</value>
				  </parameter>
				  <parameter xmlns="">
					  <name>Narration</name>
					  <value>'.$Narration.'</value>
				  </parameter>
				  <parameter xmlns="">
					  <name>appVersion</name>
					  <value>'.$appVersion.'</value>
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

}
