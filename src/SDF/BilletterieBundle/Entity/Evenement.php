<?php

namespace SDF\BilletterieBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Evenement
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="SDF\BilletterieBundle\Entity\EvenementRepository")
 */
class Evenement
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
     * @ORM\Column(name="nom", type="string", length=255)
     */
    private $nom;

    /**
     * @var integer
     *
     * @ORM\Column(name="quantiteMax", type="integer")
     */
    private $quantiteMax;


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
     * Set nom
     *
     * @param string $nom
     * @return Evenement
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string 
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set quantiteMax
     *
     * @param integer $quantiteMax
     * @return Evenement
     */
    public function setQuantiteMax($quantiteMax)
    {
        $this->quantiteMax = $quantiteMax;

        return $this;
    }

    /**
     * Get quantiteMax
     *
     * @return integer 
     */
    public function getQuantiteMax()
    {
        return $this->quantiteMax;
    }
}
