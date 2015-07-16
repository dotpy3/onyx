<?php

namespace SDF\BilletterieBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Navette
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="SDF\BilletterieBundle\Entity\NavetteRepository")
 */
class Navette
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
     * @var \DateTime
     *
     * @ORM\Column(name="horaireDepart", type="datetime")
     */
    private $horaireDepart;

    /**
     * @var integer
     *
     * @ORM\Column(name="capaciteMax", type="integer")
     */
    private $capaciteMax;

    /**
     * @ORM\ManyToOne(targetEntity="SDF\BilletterieBundle\Entity\Trajet")
     * @ORM\JoinColumn(nullable=false)
     */
    private $trajet;


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
     * Set horaireDepart
     *
     * @param \DateTime $horaireDepart
     * @return Navette
     */
    public function setHoraireDepart($horaireDepart)
    {
        $this->horaireDepart = $horaireDepart;

        return $this;
    }

    /**
     * Get horaireDepart
     *
     * @return \DateTime 
     */
    public function getHoraireDepart()
    {
        return $this->horaireDepart;
    }

    public function getHoraireDepartFormat($format = 'H:i'){
        return $this->horaireDepart->format($format);
    }

    /**
     * Set capaciteMax
     *
     * @param integer $capaciteMax
     * @return Navette
     */
    public function setCapaciteMax($capaciteMax)
    {
        $this->capaciteMax = $capaciteMax;

        return $this;
    }

    /**
     * Get capaciteMax
     *
     * @return integer 
     */
    public function getCapaciteMax()
    {
        return $this->capaciteMax;
    }

    /**
     * Set trajet
     *
     * @param \SDF\BilletterieBundle\Entity\Trajet $trajet
     * @return Navette
     */
    public function setTrajet(\SDF\BilletterieBundle\Entity\Trajet $trajet)
    {
        $this->trajet = $trajet;

        return $this;
    }

    /**
     * Get trajet
     *
     * @return \SDF\BilletterieBundle\Entity\Trajet 
     */
    public function getTrajet()
    {
        return $this->trajet;
    }
}
