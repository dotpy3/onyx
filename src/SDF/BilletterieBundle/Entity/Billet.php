<?php

namespace SDF\BilletterieBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Billet
 *
 * @ORM\Table(name="billets")
 * @ORM\Entity(repositoryClass="SDF\BilletterieBundle\Entity\BilletRepository")
 */
class Billet
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
     * @var boolean
     *
     * @ORM\Column(name="valide", type="boolean")
     */
    private $valide;

    /**
     * @var string
     *
     * @ORM\Column(name="idPayutc", type="string", length=255, nullable=true)
     */
    private $idPayutc;

    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=255)
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="prenom", type="string", length=255)
     */
    private $prenom;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isMajeur", type="boolean")
     */
    private $isMajeur;

    /**
     * @var integer
     *
     * @ORM\Column(name="barcode", type="integer")
     */
    private $barcode;

    /**
     * @var datetime
     *
     * @ORM\Column(name="dateAchat", type="datetime")
     */
    private $dateAchat;

    /**
     * @var boolean
     *
     * @ORM\Column(name="accepteDroitImage", type="boolean")
     */
    private $accepteDroitImage;

    /**
     * @var boolea
     *
     * @ORM\Column(name="consomme", type="boolean")
     */
    private $consomme;

    /**
     * @ORM\ManyToOne(targetEntity="SDF\BilletterieBundle\Entity\Navette")
     * @ORM\JoinColumn(nullable=true)
     */
    private $navette;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="SDF\BilletterieBundle\Entity\Tarif")
     * @ORM\JoinColumn(nullable=false)
     */
    private $tarif;

    public function __toString()
    {
        return sprintf('Billet de %s %s [%d]', $this->prenom, $this->nom, $this->barcode);
    }

    public function __construct()
    {
        $this->valide = false;
        $this->consomme = false;
        $this->isMajeur = false;

        return $this;
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
     * Set valide
     *
     * @param boolean $valide
     * @return Billet
     */
    public function setValide($valide)
    {
        $this->valide = $valide;

        return $this;
    }

    /**
     * Get valide
     *
     * @return boolean
     */
    public function getValide()
    {
        return $this->valide;
    }

    /**
     * Set idPayutc
     *
     * @param string $idPayutc
     * @return Billet
     */
    public function setIdPayutc($idPayutc)
    {
        $this->idPayutc = $idPayutc;

        return $this;
    }

    /**
     * Get idPayutc
     *
     * @return string
     */
    public function getIdPayutc()
    {
        return $this->idPayutc;
    }

    /**
     * Set nom
     *
     * @param string $nom
     * @return Billet
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
     * Set prenom
     *
     * @param string $prenom
     * @return Billet
     */
    public function setPrenom($prenom)
    {
        $this->prenom = $prenom;

        return $this;
    }

    /**
     * Get prenom
     *
     * @return string
     */
    public function getPrenom()
    {
        return $this->prenom;
    }

    /**
     * Set isMajeur
     *
     * @param boolean $isMajeur
     * @return Billet
     */
    public function setIsMajeur($isMajeur)
    {
        $this->isMajeur = $isMajeur;

        return $this;
    }

    /**
     * Get isMajeur
     *
     * @return boolean
     */
    public function getIsMajeur()
    {
        return $this->isMajeur;
    }

    /**
     * Set navette
     *
     * @param Navette $navette
     * @return Billet
     */
    public function setNavette(Navette $navette = null)
    {
        $this->navette = $navette;

        return $this;
    }

    /**
     * Get navette
     *
     * @return Navette
     */
    public function getNavette()
    {
        return $this->navette;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return Billet
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set tarif
     *
     * @param Tarif $tarif
     * @return Billet
     */
    public function setTarif(Tarif $tarif)
    {
        $this->tarif = $tarif;

        return $this;
    }

    /**
     * Get tarif
     *
     * @return Tarif
     */
    public function getTarif()
    {
        return $this->tarif;
    }

    /**
     * Set accepteDroitImage
     *
     * @param boolean $accepteDroitImage
     * @return Billet
     */
    public function setAccepteDroitImage($accepteDroitImage)
    {
        $this->accepteDroitImage = $accepteDroitImage;

        return $this;
    }

    /**
     * Get accepteDroitImage
     *
     * @return boolean
     */
    public function getAccepteDroitImage()
    {
        return $this->accepteDroitImage;
    }

    /**
     * Set barcode
     *
     * @param integer $barcode
     * @return Billet
     */
    public function setBarcode($barcode)
    {
        $this->barcode = $barcode;

        return $this;
    }

    /**
     * Get barcode
     *
     * @return integer
     */
    public function getBarcode()
    {
        return $this->barcode;
    }

    /**
     * Set dateAchat
     *
     * @param \DateTime $dateAchat
     * @return Billet
     */
    public function setDateAchat($dateAchat)
    {
        $this->dateAchat = $dateAchat;

        return $this;
    }

    /**
     * Get dateAchat
     *
     * @return \DateTime
     */
    public function getDateAchat()
    {
        return $this->dateAchat;
    }

    /**
     * Set consomme
     *
     * @param boolean $consomme
     * @return Billet
     */
    public function setConsomme($consomme)
    {
        $this->consomme = $consomme;

        return $this;
    }

    /**
     * Get consomme
     *
     * @return boolean
     */
    public function getConsomme()
    {
        return $this->consomme;
    }
}
