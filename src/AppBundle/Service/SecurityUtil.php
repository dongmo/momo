<?php

namespace AppBundle\Service;
use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * By DassiOrleando
 * Class SecurityUtil
 * To get the current user and other nearest function.
 * @package AppBundle\Service
 */
class SecurityUtil
{
    private $token_storage;
    private $em;

    public function __construct(TokenStorageInterface $token_storage, EntityManager $entityManager)
    {
        $this->token_storage = $token_storage;
        $this->em = $entityManager;
    }

    /**
     * Return the current user.
     * @return mixed|void
     */
    public function getCurrentUser()
    {
        if (null === $token = $this->token_storage->getToken()) {
            return;
        }

        if (!is_object($user = $token->getUser())) {
            return;
        }

        return $user;
    }

    /**
     * Get necessarily developer account or throw an exception.
     *
     * @return \UserBundle\Entity\User
     * @throws AccessDeniedException
     */
    public function getHardlyDeveloperAccount()
    {
        $user = $this->getCurrentUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        return $user;
    }
}
