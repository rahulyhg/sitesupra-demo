<?php

namespace Supra\Tests\Search;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;

class DiscriminatorAppender
{
	/**
	 * @param LoadClassMetadataEventArgs $eventArgs
	 */
	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		$classMetadata = $eventArgs->getClassMetadata();
		
		if ($classMetadata->name == 'Supra\Tests\Search\Entity\DummyIndexerQueueItem') {
	
			$em = $eventArgs->getEntityManager();
			
			$discriminatorMap = $classMetadata->discriminatorMap;
			$discriminatorMap['dummy'] = $classMetadata->name;
		
			$classMetadata->setDiscriminatorMap($discriminatorMap);
			
			foreach($classMetadata->parentClasses as $parentClass) {
				
				$parentClassMetadata = $em->getClassMetadata($parentClass);
				
				$discriminatorMap = $parentClassMetadata->discriminatorMap;
				$discriminatorMap['dummy'] = $classMetadata->name;
				
				$parentClassMetadata->setDiscriminatorMap($discriminatorMap);	
			}
		}
		
	}
}