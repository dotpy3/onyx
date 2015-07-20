<?php

namespace SDF\BilletterieBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CasUser
 *
 * @ORM\Table(name="cas_users")
 * @ORM\Entity(repositoryClass="SDF\BilletterieBundle\Entity\CasUserRepository")
 */
class CasUser extends User
{
    /**
     * @var string
     *
     * @ORM\Column(name="badge", type="string", length=255)
     */
    protected $badge;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_bde_contributor", type="boolean")
     */
    protected $isBdeContributor;

    /**
     * Set badge
     *
     * @param string $badge
     * @return CasUser
     */
    public function setBadge($badge)
    {
        $this->badge = $badge;

        return $this;
    }

    /**
     * Get badge
     *
     * @return string
     */
    public function getBadge()
    {
        return $this->badge;
    }

    /**
     * Set isBdeContributor
     *
     * @param boolean $isBdeContributor
     * @return CasUser
     */
    public function setIsBdeContributor($isBdeContributor)
    {
        $this->isBdeContributor = $isBdeContributor;

        return $this;
    }

    /**
     * Get isBdeContributor
     *
     * @return boolean
     */
    public function getIsBdeContributor()
    {
        return $this->isBdeContributor;
    }
}
