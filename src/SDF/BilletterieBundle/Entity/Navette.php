<?php

namespace SDF\BilletterieBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use SDF\BilletterieBundle\Exception\UndefinedValueException;

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
     * @var integer
     *
     * The number of remaining places in this shuttle
     * This is not an SQL column, it will be set / unset by the application itself.
     */
    private $remainingPlaces;


    public function __toString()
    {
        $stringRepresentation = $this->trajet->getLieuDepart() . ' - ' . $this->trajet->getLieuArrivee() . ' : ' . $this->horaireDepart->format('H\hi');

        if (!is_null($this->remainingPlaces)) {
            $stringRepresentation .= ' [' . $this->remainingPlaces . ' places restantes]';
        }

        return $stringRepresentation;
    }


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

    /**
     * Set remainingPlaces
     *
     * @param boolean $remainingPlaces
     * @return Navette
     */
    public function setRemainingPlaces($remainingPlaces)
    {
        $this->remainingPlaces = (integer) $remainingPlaces;

        return $this;
    }

    /**
     * Get remainingPlaces
     *
     * @return boolean
     */
    public function getRemainingPlaces()
    {
        return $this->remainingPlaces;
    }

    /**
     * Is the shuttle full or not
     *
     * @throws UndefinedValueException In case the number of remaining places has not yet been set by the application
     * @return boolean
     */
    public function isFull()
    {
        if (is_null($this->remainingPlaces)) {
            throw new UndefinedValueException('The number of remaining places has not been set yet.');
        }

        return ($this->remainingPlaces === 0);
    }
}
