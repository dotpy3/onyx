<?php

namespace SDF\BilletterieBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UtilisateurExterieur
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="SDF\BilletterieBundle\Entity\UtilisateurExterieurRepository")
 */
class UtilisateurExterieur
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
     * @ORM\Column(name="motDePasse", type="string", length=255)
     */
    private $motDePasse;

    /**
     * @var string
     *
     * @ORM\Column(name="login", type="string", length=255)
     */
    private $login;


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
     * Set motDePasse
     *
     * @param string $motDePasse
     * @return UtilisateurExterieur
     */
    public function setMotDePasse($motDePasse)
    {
        $this->motDePasse = $motDePasse;

        return $this;
    }

    /**
     * Get motDePasse
     *
     * @return string 
     */
    public function getMotDePasse()
    {
        return $this->motDePasse;
    }

    /**
     * Set login
     *
     * @param string $login
     * @return UtilisateurExterieur
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    /**
     * Get login
     *
     * @return string 
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Set user
     *
     * @param \SDF\BilletterieBundle\Entity\Utilisateur $user
     * @return UtilisateurExterieur
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
