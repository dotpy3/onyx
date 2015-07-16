<?php

namespace SDF\BilletterieBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trajet
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="SDF\BilletterieBundle\Entity\TrajetRepository")
 */
class Trajet
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
     * @ORM\Column(name="lieuDepart", type="string", length=255)
     */
    private $lieuDepart;

    /**
     * @var string
     *
     * @ORM\Column(name="lieuArrivee", type="string", length=255)
     */
    private $lieuArrivee;


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
     * Set lieuDepart
     *
     * @param string $lieuDepart
     * @return Trajet
     */
    public function setLieuDepart($lieuDepart)
    {
        $this->lieuDepart = $lieuDepart;

        return $this;
    }

    /**
     * Get lieuDepart
     *
     * @return string 
     */
    public function getLieuDepart()
    {
        return $this->lieuDepart;
    }

    /**
     * Set lieuArrivee
     *
     * @param string $lieuArrivee
     * @return Trajet
     */
    public function setLieuArrivee($lieuArrivee)
    {
        $this->lieuArrivee = $lieuArrivee;

        return $this;
    }

    /**
     * Get lieuArrivee
     *
     * @return string 
     */
    public function getLieuArrivee()
    {
        return $this->lieuArrivee;
    }
}
