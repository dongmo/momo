<?php
/**
 * Created by Samuel.
 * Date: 26/09/2016
 * Time: 06:49
 */
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Api controller.
 * Just for testing website OAuth Rest call with clientId & clientSecret.
 *
 * @Route("/api")
 */
class ApiController extends Controller
{
    /**
     * @Route("/ping", name="api_ping")
     * @Method({"GET", "POST"})
     */
    public function pingAction(Request $request)
    {

        $tokenManager = $this->container->get('fos_oauth_server.access_token_manager.default');
        $token        = $this->get('security.token_storage')->getToken();
        $accessToken  = $tokenManager->findTokenByToken($token->getToken());
        $client = $accessToken->getClient();

        // Validate account before performing a deposit.
        $developerValidatorV1Service = $this->get('developerValidatorV1Service');
        $application = $developerValidatorV1Service->validateExistingApplication($client);

        // Invalid developer account.
        if(is_null($application)){
            return new JsonResponse([
                'error' => "yes",
                'message' => "Not valid developer account/application"
            ]);
        }

        return new JsonResponse([
            'error' => "no",
            'message' => "Valid developer account",
            'application' => $application->getName(),
            'phoneNumber' => $application->getPhoneNumber(),
            'fullName' => $application->getUser()->getFullName()
        ]);
    }
}
