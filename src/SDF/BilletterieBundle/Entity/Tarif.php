<?php

namespace SDF\BilletterieBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tarif
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="SDF\BilletterieBundle\Entity\TarifRepository")
 */
class Tarif
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
     * @ORM\Column(name="prix", type="decimal")
     */
    private $prix;

    /**
     * @var integer
     *
     * @ORM\Column(name="quantite", type="integer")
     */
    private $quantite;

    /**
     * @var integer
     *
     * @ORM\Column(name="quantiteParPersonne", type="integer")
     */
    private $quantiteParPersonne;

    /**
     * @var string
     *
     * @ORM\Column(name="nomTarif", type="string", length=255)
     */
    private $nomTarif;

    /**
     * @var integer
     *
     * @ORM\Column(name="idPayutc", type="integer")
     */
    private $idPayutc;

    /**
     * @ORM\ManyToOne(targetEntity="SDF\BilletterieBundle\Entity\Contraintes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $contraintes;

    /**
     * @ORM\ManyToOne(targetEntity="SDF\BilletterieBundle\Entity\Evenement")
     * @ORM\JoinColumn(nullable=false)
     */
    private $evenement;

    /**
     * @ORM\ManyToOne(targetEntity="SDF\BilletterieBundle\Entity\PotCommunTarifs")
     * @ORM\JoinColumn(nullable=true)
     */
    private $potCommun;

    public function __toString()
    {
        return $this->nomTarif;
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
     * Set prix
     *
     * @param string $prix
     * @return Tarif
     */
    public function setPrix($prix)
    {
        $this->prix = $prix;

        return $this;
    }

    /**
     * Get prix
     *
     * @return string
     */
    public function getPrix()
    {
        return $this->prix;
    }

    /**
     * Set quantite
     *
     * @param integer $quantite
     * @return Tarif
     */
    public function setQuantite($quantite)
    {
        $this->quantite = $quantite;

        return $this;
    }

    /**
     * Get quantite
     *
     * @return integer
     */
    public function getQuantite()
    {
        return $this->quantite;
    }

    /**
     * Set quantiteParPersonne
     *
     * @param integer $quantiteParPersonne
     * @return Tarif
     */
    public function setQuantiteParPersonne($quantiteParPersonne)
    {
        $this->quantiteParPersonne = $quantiteParPersonne;

        return $this;
    }

    /**
     * Get quantiteParPersonne
     *
     * @return integer
     */
    public function getQuantiteParPersonne()
    {
        return $this->quantiteParPersonne;
    }

    /**
     * Set nomTarif
     *
     * @param string $nomTarif
     * @return Tarif
     */
    public function setNomTarif($nomTarif)
    {
        $this->nomTarif = $nomTarif;

        return $this;
    }

    /**
     * Get nomTarif
     *
     * @return string
     */
    public function getNomTarif()
    {
        return $this->nomTarif;
    }

    /**
     * Set idPayutc
     *
     * @param integer $idPayutc
     * @return Tarif
     */
    public function setIdPayutc($idPayutc)
    {
        $this->idPayutc = $idPayutc;

        return $this;
    }

    /**
     * Get idPayutc
     *
     * @return integer
     */
    public function getIdPayutc()
    {
        return $this->idPayutc;
    }

    /**
     * Set contraintes
     *
     * @param \SDF\BilletterieBundle\Entity\Contraintes $contraintes
     * @return Tarif
     */
    public function setContraintes(\SDF\BilletterieBundle\Entity\Contraintes $contraintes)
    {
        $this->contraintes = $contraintes;

        return $this;
    }

    /**
     * Get contraintes
     *
     * @return \SDF\BilletterieBundle\Entity\Contraintes
     */
    public function getContraintes()
    {
        return $this->contraintes;
    }

    /**
     * Set evenement
     *
     * @param \SDF\BilletterieBundle\Entity\Evenement $evenement
     * @return Tarif
     */
    public function setEvenement(\SDF\BilletterieBundle\Entity\Evenement $evenement)
    {
        $this->evenement = $evenement;

        return $this;
    }

    /**
     * Get evenement
     *
     * @return \SDF\BilletterieBundle\Entity\Evenement
     */
    public function getEvenement()
    {
        return $this->evenement;
    }

    /**
     * Set potCommun
     *
     * @param \SDF\BilletterieBundle\Entity\PotCommunTarifs $potCommun
     * @return Tarif
     */
    public function setPotCommun(\SDF\BilletterieBundle\Entity\PotCommunTarifs $potCommun = null)
    {
        $this->potCommun = $potCommun;

        return $this;
    }

    /**
     * Get potCommun
     *
     * @return \SDF\BilletterieBundle\Entity\PotCommunTarifs
     */
    public function getPotCommun()
    {
        return $this->potCommun;
    }
}
