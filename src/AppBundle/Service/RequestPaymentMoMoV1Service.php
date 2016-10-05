<?php
/**
 * Created by Samuel.
 * Date: 29/09/2016
 * Time: 04:48
 */

namespace AppBundle\Service;

use AppBundle\Controller\DefaultController;
use AppBundle\Entity\Client;
use AppBundle\Entity\Transaction;
use AppBundle\Service\DepositMoneyService;
use AppBundle\Service\RequestPaymentService;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class DepositMoMoV1Service
 * Service to make a MoMo deposit transaction
 * and get an email after a successful opération.
 *
 * @package ApiBundle\service
 */
class RequestPaymentMoMoV1Service
{
    /**
     * Logger logger
     *
     * @var LoggerInterface
     */
    private $depositMoneyService;
    private $requestPaymentService;
    private $requestStack;
    private $logger;
    private $developerValidatorV1Service;
    private $em;
    private $mailer; // Service to send mail after successful transaction.
    private $router;

    public function __construct(DepositMoneyService $depositMoneyService, RequestPaymentService $requestPaymentService, RequestStack $requestStack, LoggerInterface $logger,
                                DeveloperValidatorV1Service $developerValidatorV1Service,
                                EntityManager $entityManager, $mailer, RouterInterface $router)
    {
        $this->depositMoneyService = $depositMoneyService;
        $this->requestPaymentService = $requestPaymentService;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
        $this->developerValidatorV1Service = $developerValidatorV1Service;
        $this->em = $entityManager;
        $this->mailer = $mailer;
        $this->router = $router;
    }

    public function requestOfMoney(Client $client){
        $request = $this->requestStack->getCurrentRequest();
        $phoneNumber = $request->query->get("phoneNumber");
        $amount = $request->query->get("amount");
        $transactionId = $request->query->get("transactionId");

        // Validate account before performing a deposit.
        $application = $this->developerValidatorV1Service->validateExistingApplication($client);

        // Invalid developer account.
        if(is_null($application)){
            return [
                'error' => "yes",
                'message' => "Not valid developer account/application"
            ];
        }

        // We make all validation here.
        $error = $this->validate($phoneNumber, $amount);
        if(!is_null($error)){
            return $error;
        }

        // Get the fees of deposit.
       // $transactionFees = $application->getPricing()->getTransactionFees();
       // $fees = ($amount * $transactionFees)/100;

        // After validation we can now perform deposit request and save transaction.
        $momoResponse = $this->requestPaymentService->requestPayment($amount, $phoneNumber);

//        if($momoResponse->getReasonPhrase() === "OK") {
        $xml = $this->catchMoMoResponse($momoResponse);

        // Start: Catch all errors here
        // params
        if(isset($xml->faultcode)){
            if($xml->faultcode == 100){
                return array('code' => '0013',
                    'message' => 'ErrorParams: Invalid Amount or PhoneNumber');
            }
        }

        // Timeout and others Curl exceptions.
        if(isset($xml->datas)){
            $this->logger->info("Result timeout: " . $xml->datas);
            if($xml->datas->error === "Yes"){
                // return array('code' => $xml->datas->code, 'message' => $xml->datas->message);
                return array('code' => '0', 'message' => $xml->datas->message);
            }
        }
        // End: Catch all errors here

        $processingNumber = "00000";
        $statusCode = "00000";
        if(isset($xml->returns)) {
            foreach ($xml->children()->children() as $return) {
                $this->logger->info("Returns elements of xml: " . $return->name . ", value: " . $return->value);
                if (isset($return->name)) {
                    $name = (string) $return->name;
                    if ($name == "ProcessingNumber") {
                        $processingNumber = (string) $return->value;
                    }
                    if ($name == "StatusCode") {
                        $statusCode = (string) $return->value;
                    }
                }
            }
            // Catch transaction errors.
            $returnErrorArray = $this->redirectErrorResponse($statusCode, $processingNumber,$transactionId);
            if(!is_null($returnErrorArray)){
                return $returnErrorArray;
            }



          /*  if($statusCode == "1000"){
               // sleep(10);
                //$defaultController = new DefaultController();
                //redirect($this->generateUrl('request_payment_completed'));
                //return new RedirectResponse($this->router->generate('request_payment_completed'));
                //return $defaultController->generateUrl("request_payment_completed");
                return array('code' => '1000', 'message' => "Pending");
            }*/

            // Case success payment transaction.
            if ($statusCode == "01") {
                return array('code' => '200', 'message' => 'Paiement MoMo effectue avec succes. Numero de transaction ' . $processingNumber . '.','processingIdentifier' => $processingNumber,'transactionId' => $transactionId);
            }
        }else{
            return array('code' => '0000', 'message' => "Request Time Out ! Please retry",'processingIdentifier' => $processingNumber,'transactionId' => $transactionId);
        }

        return array(
            'code' => '0',
            'message' => 'Request timeout: 120 seconds.',
            'processingIdentifier' => $processingNumber,
            'transactionId' => $transactionId
        );
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
            return array('code' => $code, 'processingIdentifier' => $processingNumber, 'message' => 'Paiement non validité par le client ou encore numéro non reconnu. Veuillez relancer le paiement.','transactionId' => $transactionId);
        }
        if ($code == 102) {
            return array('code' => $code, 'processingIdentifier' => $processingNumber, 'message' => 'Client Error: Invalid subscriber id','transactionId' => $transactionId);
        }
        if ($code == 108) {
            return array('code' => $code, 'processingIdentifier' => $processingNumber, 'message' => 'Client Error: Insufficient funds for deposit','transactionId' => $transactionId);
        }
        if ($code == 110) {
            return array('code' => $code, 'processingIdentifier' => $processingNumber, 'message' => 'Server Error: Source account not active','transactionId' => $transactionId);
        }
        if ($code == 111) {
            return array('code' => $code, 'processingIdentifier' => $processingNumber, 'message' => 'Client Error: Mobile Account number is not active','transactionId' => $transactionId);
        }
        if ($code == 105) {
            return array('code' => $code, 'processingIdentifier' => $processingNumber, 'message' => 'Client Error: Non-existent Mobile account','transactionId' => $transactionId);
        }
        return null;
    }

    /**
     * @param $momoResponse
     * @return SimpleXMLElement
     */
    private function catchMoMoResponse($momoResponse)
    {

        //$body = (string)$momoResponse->getBody();
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