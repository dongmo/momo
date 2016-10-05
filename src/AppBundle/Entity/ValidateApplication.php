<?php
/**
 * Created by DassiOrleando.
 * Date: 08/09/2016
 * Time: 12:19
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="validate_application")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ValidateApplicationRepository")
 */
class ValidateApplication
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $verifiedPhoneNumber = false;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $verifiedSetupFees = false;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return boolean
     */
    public function isVerifiedPhoneNumber()
    {
        return $this->verifiedPhoneNumber;
    }

    /**
     * @param boolean $verifiedPhoneNumber
     */
    public function setVerifiedPhoneNumber($verifiedPhoneNumber)
    {
        $this->verifiedPhoneNumber = $verifiedPhoneNumber;
    }

    /**
     * @return boolean
     */
    public function isVerifiedSetupFees()
    {
        return $this->verifiedSetupFees;
    }

    /**
     * @param boolean $verifiedSetupFees
     */
    public function setVerifiedSetupFees($verifiedSetupFees)
    {
        $this->verifiedSetupFees = $verifiedSetupFees;
    }

}