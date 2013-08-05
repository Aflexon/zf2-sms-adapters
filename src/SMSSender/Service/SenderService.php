<?php
/**
 * @author Andrey Lis <me@andreylis.ru>
 */

namespace SMSSender\Service;

use Doctrine\ORM\EntityManager;
use SMSSender\Entity\Message;
use SMSSender\Entity\MessageInterface;
use SMSSender\Exception\RuntimeException;
use SMSSender\Repository\MessageRepository;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;

class SenderService implements  ServiceLocatorAwareInterface
{

    use ServiceLocatorAwareTrait, OptionsTrait;

    /**
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * @param $phone string
     * @param $text string
     */
    public function sendSMS($phone, $text)
    {
        $message = $this->getMessageRepository()->factorySMS();
        $message->setMessage($text);
        $message->setRecipient($phone);
        $this->getMessageRepository()->sendMessage($message);
    }


    public function processUnprocessed()
    {
        foreach ($this->getMessageRepository()->loadUnprocessed() as $message) {
            $this->directSend($message);
        }
    }

    public function directSend(MessageInterface $message)
    {
        $adapter = $this->getServiceLocator()->get($this->getSenderOptions()->getProvider());
        try {
            $adapter->send($message);
            $message->setSent();
        } catch (RuntimeException $e) {
            $message->setFailed();
        }

        $this->getEntityManager()->persist($message);
    }

    /**
     * @return MessageRepository
     */
    public function getMessageRepository()
    {
        return $this->getEntityManager()->getRepository('SMSSender\Entity\Message');
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->getServiceLocator()->get('entity_manager');
    }

}
