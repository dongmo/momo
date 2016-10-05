<?php
/**
 * Created by DassiOrleando.
 * Date: 13/09/2016
 * Time: 11:09
 */

// src/ApiBundle/Entity/Transaction.php
namespace AppBundle\Entity;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use UserBundle\Entity\User;
use AppBundle\Entity\Application;

/**
 * @ORM\Table(name="transaction")
 * @ORM\Entity(repositoryClass="ApiBundle\Repository\TransactionRepository")
 */
class Transaction
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
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Application")
     */
    protected $application;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $date;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false)
     */
    protected $amount;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=false)
     */
    protected $cost;

    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $customerNumber;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $status;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    protected $processingIdentifier;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    protected $depositIdentifier;

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @param mixed $application
     */
    public function setApplication($application)
    {
        $this->application = $application;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param mixed $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return float
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * @param float $cost
     */
    public function setCost($cost)
    {
        $this->cost = $cost;
    }

    /**
     * @return int
     */
    public function getCustomerNumber()
    {
        return $this->customerNumber;
    }

    /**
     * @param int $customerNumber
     */
    public function setCustomerNumber($customerNumber)
    {
        $this->customerNumber = $customerNumber;
    }

    /**
     * @return boolean
     */
    public function isStatus()
    {
        return $this->status;
    }

    /**
     * @param boolean $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getProcessingIdentifier()
    {
        return $this->processingIdentifier;
    }

    /**
     * @param mixed $processingIdentifier
     */
    public function setProcessingIdentifier($processingIdentifier)
    {
        $this->processingIdentifier = $processingIdentifier;
    }

    /**
     * @return mixed
     */
    public function getDepositIdentifier()
    {
        return $this->depositIdentifier;
    }

    /**
     * @param mixed $depositIdentifier
     */
    public function setDepositIdentifier($depositIdentifier)
    {
        $this->depositIdentifier = $depositIdentifier;
    }

}
