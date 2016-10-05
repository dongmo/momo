<?php
/**
 * Created by DassiOrleando.
 * Date: 15/09/2016
 * Time: 05:01
 */

namespace AppBundle\Service;

use AppBundle\Entity\Client;
use AppBundle\Entity\Application;
use AppBundle\Service\SecurityUtil;
use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class DeveloperValidatorV1Service
 * Service to validate a developer/application in our system.
 * @package AppBundle\service
 */
class DeveloperValidatorV1Service
{
    private $securityUtil;
    private $em;

    public function __construct(SecurityUtil $securityUtil, EntityManager $entityManager)
    {
        $this->securityUtil = $securityUtil;
        $this->em = $entityManager;
    }

    /**
     * @return \UserBundle\Entity\User
     */
    public function verifyDeveloperAccount(){
        // Here we are sure to have a developer logged in.
        $currentDeveloper = $this->securityUtil->getHardlyDeveloperAccount();
        return $currentDeveloper;
    }

    /**
     * Validate that this request corresponding to associated developer and application via her clientID & clientSecret.
     * @param Client $client
     * @return mixed|null
     */
    public function validateExistingApplication(Client $client){
        // Here we are sure to have a developer logged in.
        $currentDeveloper = $this->verifyDeveloperAccount();

        $application = $this->em->getRepository('AppBundle:Application')
                                    ->findOneBy(
                                        array('client' => $client)
                                    );

        $user = $application->getUser();

        if($user->getId() != $currentDeveloper->getId()){
            return null; // It's not the application of the current developer.
        }

        return $application;
    }

}