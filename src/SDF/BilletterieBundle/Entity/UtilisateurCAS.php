<?php

namespace SDF\BilletterieBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UtilisateurCAS
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="SDF\BilletterieBundle\Entity\UtilisateurCASRepository")
 */
class UtilisateurCAS
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="userBadge", type="string", length=255)
     */
    private $userBadge;

    /**
     * @var boolean
     *
     * @ORM\Column(name="cotisant", type="boolean")
     */
    private $cotisant;

    /**
     * @var string
     *
     * @ORM\Column(name="loginCAS", type="string", length=255)
     */
    private $loginCAS;


    /**
     * @ORM\OneToOne(targetEntity="SDF\BilletterieBundle\Entity\Utilisateur", cascade={"persist"})
     */
    private $user;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set userBadge
     *
     * @param string $userBadge
     * @return UtilisateurCAS
     */
    public function setUserBadge($userBadge)
    {
        $this->userBadge = $userBadge;

        return $this;
    }

    /**
     * Get userBadge
     *
     * @return string 
     */
    public function getUserBadge()
    {
        return $this->userBadge;
    }

    /**
     * Set cotisant
     *
     * @param boolean $cotisant
     * @return UtilisateurCAS
     */
    public function setCotisant($cotisant)
    {
        $this->cotisant = $cotisant;

        return $this;
    }

    /**
     * Get cotisant
     *
     * @return boolean 
     */
    public function getCotisant()
    {
        return $this->cotisant;
    }

    /**
     * Set loginCAS
     *
     * @param string $loginCAS
     * @return UtilisateurCAS
     */
    public function setLoginCAS($loginCAS)
    {
        $this->loginCAS = $loginCAS;

        return $this;
    }

    /**
     * Get loginCAS
     *
     * @return string 
     */
    public function getLoginCAS()
    {
        return $this->loginCAS;
    }

    /**
     * Set user
     *
     * @param \SDF\BilletterieBundle\Entity\Utilisateur $user
     * @return UtilisateurCAS
     */
    public function setUser(\SDF\BilletterieBundle\Entity\Utilisateur $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \SDF\BilletterieBundle\Entity\Utilisateur 
     */
    public function getUser()
    {
        return $this->user;
    }
}
