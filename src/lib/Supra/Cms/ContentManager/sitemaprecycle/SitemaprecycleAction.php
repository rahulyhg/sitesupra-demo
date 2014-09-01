<?php

namespace Supra\Cms\ContentManager\Sitemaprecycle;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Entity;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\PageRevisionData;
use Supra\Controller\Pages\Event;

/**
 * Sitemap
 */
class SitemaprecycleAction extends PageManagerAction
{

	public function sitemapAction()
	{
		$response = $this->loadSitemapTree(Entity\PageLocalization::CN());

		$this->getResponse()
				->setResponseData($response);
	}

	public function templatesAction()
	{
		$response = $this->loadSitemapTree(Entity\TemplateLocalization::CN());

		$this->getResponse()
				->setResponseData($response);
	}

	public function restoreAction()
	{
		$this->lock();
		
		$eventArgs = new Event\PageCmsEventArgs;
		$eventArgs->user = $this->getUser();
		
		$eventManager = ObjectRepository::getEventManager($this);
		$eventManager->fire(Event\PageCmsEvents::pagePreRestore, $eventArgs);
		
		// Main
		$this->restorePageVersion();

		$this->unlock();

		$pageData = $this->getPageLocalization();
		
		$eventArgs->localization = $pageData;
		$eventManager->fire(Event\PageCmsEvents::pagePostRestore, $eventArgs);
		
		$this->writeAuditLog('%item% restored', $pageData);
	}

	protected function loadSitemapTree($entity)
	{
		$pages = array();
		$response = array();

		$localeId = $this->getLocale()->getId();

		$auditEm = ObjectRepository::getEntityManager('#audit');
		
		$userProvider = ObjectRepository::getUserProvider($this);

		$searchCriteria = array(
			'locale' => $localeId,
			'type' => PageRevisionData::TYPE_TRASH,
		);
		
		$qb = $auditEm->createQueryBuilder()
				->from($entity, 'l')
				->from(PageRevisionData::CN(), 'r')
				->select('l.id, l.title, l.revision, l.master, r.creationTime, r.user')
				->andWhere('r.type = :type')
				->andWhere('l.locale = :locale')
				->andWhere('l.revision = r.id')
				->orderBy('r.creationTime', 'DESC');

		if ($entity == Entity\PageLocalization::CN()) {
			$qb->addSelect('l.pathPart, l.template');
			
			// limit by application type
			$typeFilter = $this->getRequestParameter('filter');
			if ( ! empty($typeFilter)) {
				$qb->andWhere('l.parentPageApplicationId = :applicationType');
				$searchCriteria['applicationType'] = $typeFilter;
			}
		}

		$localizationDataList = $qb->getQuery()
				->execute($searchCriteria, \Doctrine\ORM\Query::HYDRATE_ARRAY);

		foreach ($localizationDataList as $localizationData) {

			$pageInfo = array();
			$pathPart = null;
			$templateId = null;

			if ($entity == Entity\PageLocalization::CN()) {
				$pathPart = $localizationData['pathPart'];
				$templateId = $localizationData['template'];
			}
			
			$userId = $localizationData['user'];
			// 8 characters is enough if user was not found
			$userName = '#' . substr($userId, 0 ,8);
			if ( ! is_null($userId)) {
				$user = $userProvider->findUserById($userId);
				if ($user instanceof \Supra\User\Entity\User) {
					$userName = $user->getName();
				}
			}

			$timeTrashed = $localizationData['creationTime']->format('c');
			
			$pageInfo = array(
				'id' => $localizationData['id'],
				'date' => $timeTrashed,
				'title' => $localizationData['title'],
				'revision' => $localizationData['revision'],
				'author' => $userName,
				'path' => $pathPart,
				'template' => $templateId,
				// TODO: do we need this?
				'localized' => true,
				'published' => false,
				'scheduled' => false,
			);
			
			$response[] = $pageInfo;
		}

		return $response;
	}

}