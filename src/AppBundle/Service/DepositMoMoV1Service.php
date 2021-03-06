<?php
/**
 * Created by Samuel.
 * Date: 29/09/2016
 * Time: 04:48
 */

namespace AppBundle\Service;

use AppBundle\Entity\Client;
use AppBundle\Service\DepositMoneyService;
use Doctrine\ORM\EntityManager;
use DOMDocument;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

/**
 * Class DepositMoMoV1Service
 * Service to make a MoMo deposit transaction
 * and get an email after a successful opération.
 *
 * @package AppBundle\service
 */
class DepositMoMoV1Service
{
    /**
     * Logger logger
     *
     * @var LoggerInterface
     */
    private $depositMoneyService;
    private $requestStack;
    private $logger;
    private $developerValidatorV1Service;
    private $em;
    private $mailer; // Service to send mail after successful transaction.

    public function __construct(DepositMoneyService $depositMoneyService, RequestStack $requestStack, LoggerInterface $logger,
                                DeveloperValidatorV1Service $developerValidatorV1Service,
                                EntityManager $entityManager, $mailer)
    {
        $this->depositMoneyService = $depositMoneyService;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
        $this->developerValidatorV1Service = $developerValidatorV1Service;
        $this->em = $entityManager;
        $this->mailer = $mailer;
    }

    public function depositOfMoney(Client $client){
        $request = $this->requestStack->getCurrentRequest();
        $phoneNumber = $request->query->get("phoneNumber");
        $amount = $request->query->get("amount");
        $transactionId = $request->query->get("transactionId");



       /* // Validate account before performing a deposit.
        //$application = null;
        // $this->developerValidatorV1Service->validateExistingApplication($client);


        // Invalid developer account.
        if(is_null($application)){
            return [
                'error' => "yes",
                'message' => "Not valid developer account/application"
            ];
        }*/

        // echo "hgh";
        // We make all validation here.
        $error = $this->validate($phoneNumber, $amount);
        if(!is_null($error)){
            return $error;
        }

        // After validation we can now perform deposit request and save transaction.
        $momoDepositResponse = $this->depositMoneyService->sendMoney($amount, $phoneNumber);

        $xmlDeposit = $this->catchMoMoResponse($momoDepositResponse);

//        if(isset($xmlDeposit->faultcode)){
//            if((string)$xml->returns->faultcode == '04'){
//                return array('code' => '0014', 'message' => 'ErrorDeposit: Payment Amount is not in range.');
//            }
//        }

        if(isset($xmlDeposit->faultcode)){
            if($xmlDeposit->faultcode == 100){
                return array('code' => '0014', 'message' => 'ErrorParams: Invalid Amount or PhoneNumber');
            }
        }

        // Timeout and others Curl exceptions.
        if(isset($xmlDeposit->data)){
            $this->logger->info("Result timeout: " . $xmlDeposit->data);
            if($xmlDeposit->data->error === "Yes"){
                // return array('code' => $xml->datas->code, 'message' => $xml->datas->message);
                return array('code' => '0000', 'message' => $xmlDeposit->data->message);
                // return array('code' => $xmlDeposit->data->code, 'message' => $xmlDeposit->data->message);
            }
        }

        $processingNumberDeposit = "00000";
        $statusCodeDeposit = "00000";
        if(isset($xmlDeposit->returns)) {
            foreach ($xmlDeposit->children()->children() as $return) {
                $this->logger->info("Returns elements of xml: " . $return->name . ", value: " . $return->value);
                if (isset($return->name)) {
                    $name = (string)$return->name;
                    if ($name == "ProcessingNumber") {
                        $processingNumberDeposit = (string)$return->value;
                    }
                    if ($name == "StatusCode") {
                        $statusCodeDeposit = (string)$return->value;
                    }
                }
            }
            $returnErrorArrayDeposit = $this->redirectErrorResponse($statusCodeDeposit, $processingNumberDeposit,$transactionId);
            if (!is_null($returnErrorArrayDeposit)) {
                return $returnErrorArrayDeposit;
            }

            //Successful deposit
            if ($statusCodeDeposit == "01") {
                $message = \Swift_Message::newInstance()
                    ->setSubject('website Success Transaction')
                    ->setFrom('app@gmail.com')
                    ->setTo('app@gmail.com')
                    ->setBody('Successful deposit transaction in API with application: ');

                $this->mailer->send($message);

                // return json_encode(array('code' => '200', 'depositIdentifier' => $processingNumberDeposit, 'message' => 'Depôt MoMo effectué avec succès. Numéro de transaction ' . $processingNumberDeposit . '.'));
                return array('code' => '200', 'depositIdentifier' => $processingNumberDeposit, 'message' => 'Depot MoMo effectue avec succes. Numero de transaction ' . $processingNumberDeposit . '.','processingIdentifier' => $processingNumberDeposit,'transactionId' => $transactionId);
            }else{
                return array('code' => '405', 'message' => 'Sorry, fail deposit to number ' . $phoneNumber);
            }
        }
    }

    /**
     * @param $application
     * @return array
     */
    public function validate($phoneNumber, $amount)
    {


        // Validate request's data of developer: Start
        $errorParams = array();
        $validPhoneNumber = is_null($phoneNumber) || !isset($phoneNumber);
        if ($validPhoneNumber) {
            array_push($errorParams, "Phone", "Phone number must not be null or empty");
        }

        $validAmount = is_null($amount) || !isset($amount);
        if ($validAmount) {
            array_push($errorParams, "Amount", "Amount must not be null or empty");
        }

        if ($validPhoneNumber || $validAmount) { // || $validReference){
            array_push($errorParams, "code", "0");
            array_push($errorParams, "message", "Invalid request parameters");
            return $errorParams;
        }
        // Validate request's data of developer: End

        // Everythings is OK.
        return null;
    }

    public function redirectErrorResponse($code, $processingNumber,$transactionId)
    {
        if ($code == 100) {
            return array('code' => $code, 'message' => 'Paiement non validite par le client ou encore numero non reconnu. Veuillez relancer le paiement.','processingIdentifier' => $processingNumber,'transactionId' => $transactionId);
        }
        if ($code == 102) {
            return array('code' => $code, 'message' => 'Client Error: Invalid subscriber id','processingIdentifier' => $processingNumber,'transactionId' => $transactionId);
        }
        if ($code == 108) {
            return array('code' => $code, 'message' => 'Client Error: Insufficient funds for deposit','processingIdentifier' => $processingNumber,'transactionId' => $transactionId);
        }
        if ($code == 110) {
            return array('code' => $code, 'message' => 'Server Error: Source account not active','processingIdentifier' => $processingNumber,'transactionId' => $transactionId);
        }
        if ($code == 111) {
            return array('code' => $code, 'message' => 'Client Error: Mobile Account number is not active','processingIdentifier' => $processingNumber,'transactionId' => $transactionId);
        }
        if ($code == 105) {
            return array('code' => $code, 'message' => 'Client Error: Non-existent Mobile account','processingIdentifier' => $processingNumber,'transactionId' => $transactionId);
        }
        return null;
    }

    /**
     * @param $momoResponse
     * @return SimpleXMLElement
     */
    private function catchMoMoResponse($momoResponse)
    {
        $body = (string)$momoResponse;
        $this->logger->info("MoMo Response body: " . $body);

//            $tab1=explode("<soapenv:Fault>",$body);
//            $tab2=explode("</soapenv:Fault>",$tab1[1]);

        $tab = str_replace('<?xml version="1.0" encoding="utf-8" ?>', "", $body);
        $tab = str_replace('<?xml version="1.0" encoding="utf-8"?>', "", $tab);

        $tab = str_replace("<soapenv:Fault>", "", $tab);
        $tab = str_replace("</soapenv:Fault>", "", $tab);

        $tab = str_replace("<soapenv:Header>", "", $tab);
        $tab = str_replace("</soapenv:Header>", "", $tab);

        $tab = str_replace("<ns1:processRequestResponse xmlns:ns1=\"http://b2b.mobilemoney.mtn.zm_v1.0/\">", "", $tab);
        $tab = str_replace("<ns1:processRequestResponse xmlns:ns1=\"http://b2b.mobilemoney.mtn.zm_v1.0\">", "", $tab);
        $tab = str_replace("</ns1:processRequestResponse>", "", $tab);

        $tab = str_replace("<soapenv:Body>", "", $tab);
        $tab = str_replace("</soapenv:Body>", "", $tab);

        $tab = str_replace("<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">", "<returns>", $tab);
        $tab = str_replace("</soapenv:Envelope>", "</returns>", $tab);

        $tab = str_replace("<result>", "<datas>", $tab);
        $tab = str_replace("</result>", "</datas>", $tab);

        $this->logger->info("MoMo Response body Exploded: " . $tab);
        $xmlStr = '<?xml version="1.0" encoding="utf-8"?><result>' . $tab . '</result>';
        $this->logger->info("XML Response body to use: " . $xmlStr);

        $xml = simplexml_load_string($xmlStr);
//        var_dump($xml);

        return $xml;
    }

}
