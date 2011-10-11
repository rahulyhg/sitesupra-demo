<?php

namespace Supra\Proxy\PublicSchema;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
 */
class SupraConsoleCronEntityCronJobProxy extends \Supra\Console\Cron\Entity\CronJob implements \Doctrine\ORM\Proxy\Proxy
{
    private $_entityPersister;
    private $_identifier;
    public $__isInitialized__ = false;
    public function __construct($entityPersister, $identifier)
    {
        $this->_entityPersister = $entityPersister;
        $this->_identifier = $identifier;
    }
    /** @private */
    public function __load()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;

            if (method_exists($this, "__wakeup")) {
                // call this after __isInitialized__to avoid infinite recursion
                // but before loading to emulate what ClassMetadata::newInstance()
                // provides.
                $this->__wakeup();
            }

            if ($this->_entityPersister->load($this->_identifier, $this) === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            unset($this->_entityPersister, $this->_identifier);
        }
    }
    
    
    public function setCommandInput($commandInput)
    {
        $this->__load();
        return parent::setCommandInput($commandInput);
    }

    public function getCommandInput()
    {
        $this->__load();
        return parent::getCommandInput();
    }

    public function setLastExecutionTime(\DateTime $time)
    {
        $this->__load();
        return parent::setLastExecutionTime($time);
    }

    public function getLastExecutionTime()
    {
        $this->__load();
        return parent::getLastExecutionTime();
    }

    public function setNextExecutionTime(\DateTime $time)
    {
        $this->__load();
        return parent::setNextExecutionTime($time);
    }

    public function getNextExecutionTime()
    {
        $this->__load();
        return parent::getNextExecutionTime();
    }

    public function setPeriodClass($periodClass)
    {
        $this->__load();
        return parent::setPeriodClass($periodClass);
    }

    public function getPeriodClass()
    {
        $this->__load();
        return parent::getPeriodClass();
    }

    public function setPeriodParameter($periodParameter)
    {
        $this->__load();
        return parent::setPeriodParameter($periodParameter);
    }

    public function getPeriodParameter()
    {
        $this->__load();
        return parent::getPeriodParameter();
    }

    public function setStatus($status)
    {
        $this->__load();
        return parent::setStatus($status);
    }

    public function getStatus()
    {
        $this->__load();
        return parent::getStatus();
    }

    public function getId()
    {
        $this->__load();
        return parent::getId();
    }

    public function equals(\Supra\Database\Entity $entity)
    {
        $this->__load();
        return parent::equals($entity);
    }

    public function __toString()
    {
        $this->__load();
        return parent::__toString();
    }

    public function getProperty($name)
    {
        $this->__load();
        return parent::getProperty($name);
    }


    public function __sleep()
    {
        return array('__isInitialized__', 'commandInput', 'periodClass', 'periodParameter', 'lastExecutionTime', 'nextExecutionTime', 'status', 'id');
    }

    public function __clone()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;
            $class = $this->_entityPersister->getClassMetadata();
            $original = $this->_entityPersister->load($this->_identifier);
            if ($original === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            foreach ($class->reflFields AS $field => $reflProperty) {
                $reflProperty->setValue($this, $reflProperty->getValue($original));
            }
            unset($this->_entityPersister, $this->_identifier);
        }
        
    }
}