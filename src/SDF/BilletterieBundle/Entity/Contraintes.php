<?php

namespace SDF\BilletterieBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Contraintes
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="SDF\BilletterieBundle\Entity\ContraintesRepository")
 */
class Contraintes
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
     * @var boolean
     *
     * @ORM\Column(name="doitEtreCotisant", type="boolean")
     */
    private $doitEtreCotisant;

    /**
     * @var boolean
     *
     * @ORM\Column(name="doitNePasEtreCotisant", type="boolean")
     */
    private $doitNePasEtreCotisant;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="debutMiseEnVente", type="datetime")
     */
    private $debutMiseEnVente;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="finMiseEnVente", type="datetime")
     */
    private $finMiseEnVente;

    /**
     * @var boolean
     *
     * @ORM\Column(name="accessibleExterieur", type="boolean")
     */
    private $accessibleExterieur;

    public function __toString()
    {
        return $this->nom;
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
     * Set doitEtreCotisant
     *
     * @param boolean $doitEtreCotisant
     * @return Contraintes
     */
    public function setDoitEtreCotisant($doitEtreCotisant)
    {
        $this->doitEtreCotisant = $doitEtreCotisant;

        return $this;
    }

    /**
     * Get doitEtreCotisant
     *
     * @return boolean 
     */
    public function getDoitEtreCotisant()
    {
        return $this->doitEtreCotisant;
    }

    /**
     * Set debutMiseEnVente
     *
     * @param \DateTime $debutMiseEnVente
     * @return Contraintes
     */
    public function setDebutMiseEnVente($debutMiseEnVente)
    {
        $this->debutMiseEnVente = $debutMiseEnVente;

        return $this;
    }

    /**
     * Get debutMiseEnVente
     *
     * @return \DateTime 
     */
    public function getDebutMiseEnVente()
    {
        return $this->debutMiseEnVente;
    }

    /**
     * Set finMiseEnVente
     *
     * @param \DateTime $finMiseEnVente
     * @return Contraintes
     */
    public function setFinMiseEnVente($finMiseEnVente)
    {
        $this->finMiseEnVente = $finMiseEnVente;

        return $this;
    }

    /**
     * Get finMiseEnVente
     *
     * @return \DateTime 
     */
    public function getFinMiseEnVente()
    {
        return $this->finMiseEnVente;
    }

    /**
     * Set accessibleExterieur
     *
     * @param boolean $accessibleExterieur
     * @return Contraintes
     */
    public function setAccessibleExterieur($accessibleExterieur)
    {
        $this->accessibleExterieur = $accessibleExterieur;

        return $this;
    }

    /**
     * Get accessibleExterieur
     *
     * @return boolean 
     */
    public function getAccessibleExterieur()
    {
        return $this->accessibleExterieur;
    }

    /**
     * Set nom
     *
     * @param string $nom
     * @return Contraintes
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
     * Set doitNePasEtreCotisant
     *
     * @param boolean $doitNePasEtreCotisant
     * @return Contraintes
     */
    public function setDoitNePasEtreCotisant($doitNePasEtreCotisant)
    {
        $this->doitNePasEtreCotisant = $doitNePasEtreCotisant;

        return $this;
    }

    /**
     * Get doitNePasEtreCotisant
     *
     * @return boolean 
     */
    public function getDoitNePasEtreCotisant()
    {
        return $this->doitNePasEtreCotisant;
    }
}
