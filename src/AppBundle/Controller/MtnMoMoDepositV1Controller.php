<?php
/**
 * Created by Samuel.
 * Date: 26/09/2016
 * Time: 00:45
 */

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * MtnMoMoDeposit controller.
 *
 * @Route("/api/mtn/momo/v1")
 */
class MtnMoMoDepositV1Controller extends Controller
{
    /**
     * Make an Mtn MoMo deposit to a developer's phone number.
     *
     * @Route("/deposit", name="MoMo_deposit")
     * @Method({"GET", "POST"})
     */
    public function depositAction(Request $request)
    {
        $tokenManager = $this->container->get('fos_oauth_server.access_token_manager.default');
        $token        = $this->get('security.token_storage')->getToken();
        $accessToken  = $tokenManager->findTokenByToken($token->getToken());
        $client = $accessToken->getClient();

        // Send money via Mtn MoMo.
        $depositMoMoV1Service = $this->get('depositMoMoV1Service');
        $responseRequest = $depositMoMoV1Service->depositOfMoney($client);

       /* $logger = $this->get('logger');
        $logger->info('Deposit MoMo Request: ' . print_r($responseRequest));*/

        $response = json_encode($responseRequest);

        return new JsonResponse($responseRequest);
    }

    /**
     * Make an Mtn MoMo request payment to a customer.
     *
     * @Route("/requestpayment", name="MoMo_requestpayment")
     * @Method({"GET", "POST"})
     */
    public function requestPaymentAction(Request $request)
    {
        $tokenManager = $this->container->get('fos_oauth_server.access_token_manager.default');
        $token        = $this->get('security.token_storage')->getToken();
        $accessToken  = $tokenManager->findTokenByToken($token->getToken());
        $client = $accessToken->getClient();

        // MoMo Request payment.
        $requestPaymentMoMoV1Service = $this->get('requestPaymentMoMoV1Service');
        $responseRequest = $requestPaymentMoMoV1Service->requestOfMoney($client);

        $code = $responseRequest['code'];
        /* echo "responseRequest = ". var_dump($responseRequest)."<br/>";



         echo "Code == ".$code;*/
       /* $logger = $this->get('logger');
        $logger->info('RequestPayment MoMo Request: ' . print_r($responseRequest));*/


       /* if($code == "1000"){
            sleep(10);
            return $this->redirect($this->generateUrl('request_payment_completed'));

        }else{
            return new JsonResponse($responseRequest);
        }*/
        return new JsonResponse($responseRequest);


    }


}
