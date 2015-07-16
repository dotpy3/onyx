<?php

namespace SDF\BilletterieBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Log
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="SDF\BilletterieBundle\Entity\LogRepository")
 */
class Log
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
     * @ORM\Column(name="Date", type="datetime")
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="Content", type="text")
     */
    private $content;

    /**
   * @ORM\ManyToOne(targetEntity="SDF\BilletterieBundle\Entity\Utilisateur")
   * @ORM\JoinColumn(nullable=true)
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
     * Set date
     *
     * @param \DateTime $date
     * @return Log
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return Log
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set user
     *
     * @param \SDF\BilletterieBundle\Entity\Utilisateur $user
     * @return Log
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

    public function setLogAs($userParameter, $contentDisposed, $dateDisposed){
        $this->setContent($contentDisposed);

        $this->setDate($dateDisposed);

        $this->setUser($userParameter);

        return $this;
    }

    public function setInstantLogAs($userParamter, $contentDisposed){
        return $this->setLogAs($userParamter,$contentDisposed, new \DateTime());


    }
}
