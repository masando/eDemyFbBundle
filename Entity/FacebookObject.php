<?php

namespace eDemy\FbBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Doctrine\Common\Collections\ArrayCollection;
use eDemy\MainBundle\Entity\BaseEntity;

/**
 * @ORM\Entity(repositoryClass="eDemy\FbBundle\Entity\FacebookObjectRepository")
 * @ORM\Table()
 */
class FacebookObject extends BaseEntity
{
    public function __construct($em = null)
    {
        parent::__construct($em);
    }

    public function __toString()
    {
        //return $this->nombre;
    }

    /**
     * @ORM\Column(name="page_id", type="string", length=255)
     */
    protected $pageId;

    public function setPageId($pageId)
    {
        $this->pageId = $pageId;

        return $this;
    }

    public function getPageId()
    {
        return $this->pageId;
    }
    
    public function showPageIdInPanel()
    {
        return true;
    }

    public function showPageIdInForm()
    {
        return true;
    }

    /**
     * @ORM\Column(name="object_id", type="string", length=255)
     */
    protected $objectId;

    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;

        return $this;
    }

    public function getObjectId()
    {
        return $this->objectId;
    }
    
    public function showObjectIdInPanel()
    {
        return true;
    }

    public function showObjectIdInForm()
    {
        return true;
    }
}
