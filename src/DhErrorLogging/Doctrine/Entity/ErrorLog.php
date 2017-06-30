<?php
/**
 * @copyright  Copyright DavidHavl.com
 * @license    MIT , http://DavidHavl.com/license/MIT
 * @author     davidhavl
 */
namespace DhErrorLogging\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="error_log",
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class ErrorLog
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(
     *     name="`reference`",
     *     type="string",
     *     nullable=true
     * )
     */
    protected $reference;

    /**
     * @var string
     *
     * @ORM\Column(
     *     name="`type`",
     *     type="string",
     *     nullable=true
     * )
     */
    protected $type = 'ERROR';

    /**
     * @var string
     *
     * @ORM\Column(
     *     name="`priority`",
     *     type="string",
     *     nullable=true
     * )
     */
    protected $priority = 'DEBUG';

    /**
     * @var string
     *
     * @ORM\Column(
     *     name="`message`",
     *     type="text",
     *     nullable=true
     * )
     */
    protected $message;

    /**
     * @var string
     *
     * @ORM\Column(
     *     name="`file`",
     *     type="text",
     *     nullable=true
     * )
     */
    protected $file;

    /**
     * @var string
     *
     * @ORM\Column(
     *     name="`line`",
     *     type="string",
     *     nullable=true
     * )
     */
    protected $line;

    /**
     * @var string
     *
     * @ORM\Column(
     *     name="`trace`",
     *     type="text",
     *     nullable=true
     * )
     */
    protected $trace;

    /**
     * @var string
     *
     * @ORM\Column(
     *     name="`xdebug`",
     *     type="text",
     *     nullable=true
     * )
     */
    protected $xdebug;

    /**
     * @var string
     *
     * @ORM\Column(
     *     name="`uri`",
     *     type="text",
     *     nullable=true
     * )
     */
    protected $uri;

    /**
     * @var string
     *
     * @ORM\Column(
     *     name="`request`",
     *     type="text",
     *     nullable=true
     * )
     */
    protected $request;

    /**
     * @var string
     *
     * @ORM\Column(
     *     name="`ip`",
     *     type="string",
     *     nullable=true
     * )
     */
    protected $ip;

    /**
     * @var string
     *
     * @ORM\Column(
     *     name="`session_id`",
     *     type="string",
     *     nullable=true
     * )
     */
    protected $sessionId;
    /**
     * @var \DateTime
     *
     * @ORM\Column(
     *     type="datetime",
     *     nullable=true
     * )
     */
    protected $updated_at;

    /**
     * @var \DateTime
     *
     * @ORM\Column(
     *     type="datetime",
     *     nullable=true
     * )
     */
    protected $created_at;


    /**
     * Set identity of the item
     *
     * @param integer $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * get identity of the item
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param string $reference
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param string $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return string
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * @param string $line
     */
    public function setLine($line)
    {
        $this->line = $line;
    }

    /**
     * @return string
     */
    public function getTrace()
    {
        return $this->trace;
    }

    /**
     * @param string $trace
     */
    public function setTrace($trace)
    {
        $this->trace = $trace;
    }

    /**
     * @return string
     */
    public function getXdebug()
    {
        return $this->xdebug;
    }

    /**
     * @param string $xdebug
     */
    public function setXdebug($xdebug)
    {
        $this->xdebug = $xdebug;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return string
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param string $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @param string $sessionId
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
    }


    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     * @param \DateTime|LifecycleEventArgs $updatedAt
     */
    public function setUpdatedAt($updatedAt = null)
    {
        if ($updatedAt instanceof LifecycleEventArgs) {
            $updatedAt = new \DateTime('now');
        }
        $this->updated_at = $updatedAt;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @ORM\PrePersist
     * @param \DateTime|LifecycleEventArgs $createdAt
     */
    public function setCreatedAt($createdAt = null)
    {
        if ($createdAt instanceof LifecycleEventArgs) {
            $createdAt = new \DateTime('now');
        }
        $this->created_at = $createdAt;
    }
}