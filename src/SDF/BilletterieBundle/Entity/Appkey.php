<?php

namespace SDF\BilletterieBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Appkey
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="SDF\BilletterieBundle\Entity\AppkeyRepository")
 */
class Appkey
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
     * @ORM\Column(name="relationKey", type="string", length=255)
     */
    private $relationKey;


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
     * Set relationKey
     *
     * @param string $relationKey
     * @return Appkey
     */
    public function setRelationKey($relationKey)
    {
        $this->relationKey = $relationKey;

        return $this;
    }

    /**
     * Get relationKey
     *
     * @return string 
     */
    public function getRelationKey()
    {
        return $this->relationKey;
    }
}
