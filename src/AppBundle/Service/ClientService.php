<?php
/**
 * OAuth's Client service.
 *
 * Created by Samuel.
 * Date: 29/09/2016
 * Time: 02:25
 */

namespace AppBundle\Service;


use AppBundle\Entity\Application;
use Doctrine\ORM\EntityManager;
use FOS\OAuthServerBundle\Entity\ClientManager;

class ClientService
{
    private $em;
    private $clientManager;

    public function __construct(EntityManager $entityManager, ClientManager $clientManager)
    {
        $this->em = $entityManager;
        $this->clientManager = $clientManager;
    }

    public function createClient(Application $application){
        // $applicationRepository = $this->em
           //  ->getRepository('AppBundle:Application');
            /* ->findOneBy(
                array('processingIdentifier' => $processingNumber)
            ); */


        // $clientManager = $this->get('fos_oauth_server.client_manager.default');
        $client = $this->clientManager->createClient();
        $client->setRedirectUris(["www.idjangui.com"]); // Not yet use as he should.
        $client->setAllowedGrantTypes(["password"]);
        $client->setLastEdit(new \DateTime());

        $application->setClient($client);

        $this->em->persist($client);
        $this->em->persist($application);
        $this->em->flush();
    }

}