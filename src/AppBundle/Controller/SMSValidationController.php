<?php
/**
 * Created by Samuel.
 * Date: 26/09/2016
 * Time: 17:00
 */

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\CodeSent;


class SMSValidationController extends Controller
{
    /**
     * On génere le code et on l'envoie par SMS au développeur relativement à une Application.
     *
     * @Route("/sms/validation/generate", name="sms_validation_generate")
     */
    public function generateValidationCodeAction($applicationId)
    {
        $em = $this->getDoctrine()->getManager();
        $application = $em->getRepository('AppBundle:Application')->find($applicationId);

        if(!($application->isActivated())){
            $postUrl = "http://sms.mflashservices.com/restapi/sms/1/text/advanced";

            // Valide the selected application here.
            // Take the phone number of the application.
            $phoneNumber = $application->getPhoneNumber();

            // Credentials of mFlashServices API.
            // Dashboard de mFlashServices.com: http://sms.mflashservices.com
            $username = "idjangui";
            $password = "fdsf32Fre";

            // Generate five digit code.
            $digits = 5;
            $code =  rand(pow(10, $digits-1), pow(10, $digits)-1);
            $sms = "S'il vous plaît utilisez le code: $code pour valider votre numéro de téléphone sur Idjangui";

            // creating an object for sending SMS
            $destination = array("to" => $phoneNumber);

            $message = array("from" => "I-Djangui",
                "destinations" => array($destination),
                "text" => $sms);

            $postData = array("messages" => array($message));
            $postDataJson = json_encode($postData);

            $ch = curl_init();
            $header = array("Content-Type:application/json", "Accept:application/json");

            curl_setopt($ch, CURLOPT_URL, $postUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postDataJson);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            // response of the POST request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $responseBody = json_decode($response);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $codeSent = $em->getRepository('AppBundle:CodeSent')->find($application);

                if(is_null($codeSent)){
                    $codeSent = new CodeSent();
                }
                $codeSent->setDate(new \DateTime());
                $codeSent->setCode($code);
                $codeSent->setApplication($application);
                $em->persist($codeSent);
                $em->flush();
            } else {
                $errorException = $responseBody->requestError->serviceException->text;
                return new JsonResponse(array('status' => 'error', 'message' => $errorException));
            }
        } else {
            return new JsonResponse(array('status' => 'success', 'message' => 'Application déjà activée'));
        }

        return new JsonResponse(array('status' => 'success', 'message' => 'Code sent to' . $phoneNumber));
    }

    /**
     * On confirme le code envoyé par SMS préalablement.
     *
     * @Route("/sms/validation/confirm", name="sms_validation_confirm")
     */
    public function confirmValidationCodeAction($applicationId, $code)
    {
        $em = $this->getDoctrine()->getManager();
        $application = $em->getRepository('AppBundle:Application')->find($applicationId);

        if(!($application->isActivated())){
            $codeSent = $em->getRepository('AppBundle:CodeSent')->find($application);

            if(is_null($codeSent)){
                return new JsonResponse(array('status' => 'error', 'message' => 'Généré le code initialement avant la confirmation'));
            }

            // On obtient la différence en terme de minutes entre la date de génération du code et la date de confirmation(date actuelle)
            $now = new \DateTime();
            $diffMinutes =  $now->diff($codeSent->getDate())->format("%i");

            // Si la différence est supérieure à 15 minutes.
            if($diffMinutes > 15){
                return new JsonResponse(array('status' => 'error', 'message' => 'Code expiré, recommencez le processus'));
            } else {
                if($code == $codeSent->getCode()){
                    // here we just tell that the application is validated
                    // fdfdfd
                    $application->setActivated(true);
                    $em->persist($application);
                    $em->flush();
                    return new JsonResponse(array('status' => 'success', 'message' => 'Mauvais Code saisi'));
                } else {
                    return new JsonResponse(array('status' => 'error', 'message' => 'Mauvais Code saisi'));
                }
            }
        } else {
            return new JsonResponse(array('status' => 'error', 'message' => 'Application déjà activée'));
        }
    }

}