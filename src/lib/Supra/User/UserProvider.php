<?php

namespace Supra\User;

use Supra\User\Entity;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\EntityManager;

class UserProvider
{
	/**
	 * Validation filters
	 * @var array 
	 */
	private $validationFilters = array();
	
	/**
	 * Entity manager
	 * @var EntityManager 
	 */
	public $entityManager;
	
	/**
	 * Authentication adapter
	 * @var Authentication\AuthenticationAdapterInterface
	 */
	protected $authAdapter;
	
	/**
	 * Binds entity manager
	 */
	public function __construct()
	{
		$this->entityManager = ObjectRepository::getEntityManager($this);
	}

	/**
	 * Override the entity manager
	 * @param EntityManager $entityManager 
	 */
	public function setEntityManager(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		return $this->entityManager;
	}

	/**
	 * Adds validation filter to array
	 * @param array $validationFilter 
	 */
	public function addValidationFilter($validationFilter)
	{
		$this->validationFilters[] = $validationFilter;
	}
	
	/**
	 * Validates user with all filters
	 * @param Entity\User $user 
	 */
	public function validate(Entity\User $user)
	{
		foreach ($this->validationFilters as $filter) {
			$filter->validateUser($user);
		}
	}
	
	/**
	 * Returns authentication adapter object
	 * @return Authentication\AuthenticationAdapterInterface
	 */
	public function getAuthAdapter()
	{
		return $this->authAdapter;
	}

	/**
	 * Sets authentication adapter
	 * @param Authentication\AuthenticationAdapterInterface $authAdapter 
	 */
	public function setAuthAdapter(Authentication\AuthenticationAdapterInterface $authAdapter)
	{
		$this->authAdapter = $authAdapter;
	}

	/**
	 * Passes user to authentication adapter
	 * @param string $login 
	 * @return Entity\User
	 */
	public function authenticate($login, $password)
	{		
		$adapter = $this->getAuthAdapter();
		
		$user = $this->findUserByLogin($login);
		
		// Try finding the user from adapter
		if (empty($user)) {
			$user = $adapter->findUser($login, $password);
			
			if (empty($user)) {
				return null;
			}
			
			$this->entityManager->persist($user);
			$this->entityManager->flush();
		}
		
		$result = $adapter->authenticate($user, $password);
		
		if ( ! $result) {
			return null;
		}
		
		return $user;
	}
	
	/**
	 * Find user by login
	 * @param string $login
	 * @return Entity\User 
	 */
	public function findUserByLogin($login)
	{
		$repo = $this->entityManager->getRepository('Supra\User\Entity\User');
		$user = $repo->findOneByLogin($login);
		
		if(empty($user)) {
			return null;
		}
		return $user;
	}
	
	/**
	 * Find user by id
	 * @param string $id
	 * @return Entity\User 
	 */
	public function findUserById($id)
	{
		$repo = $this->entityManager->getRepository('Supra\User\Entity\User');
		$user = $repo->findOneById($id);
		
		if(empty($user)) {
			return null;
		}
		
		return $user;
	}
}
