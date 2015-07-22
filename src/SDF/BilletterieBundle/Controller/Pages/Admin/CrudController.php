<?php

namespace SDF\BilletterieBundle\Controller\Pages\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use SDF\BilletterieBundle\Controller\FrontController;

/**
 * Crud Base controller.
 * Provides model methods to generate entities Doctrine-like CRUD
 *
 * - list entities
 * - add new entity
 * - display entity detail
 * - edit existing entity
 * - delete entity
 *
 * @author Florent Schildknecht <florent.schildknecht@gmail.com>
 */
class CrudController extends FrontController
{
	/*
	 * Define Entity Template with namespace, entity and template name.
	 */
	protected function getTemplate($namespace, $entityName, $templateName)
	{
		return $namespace . ':Pages/Admin/Entities:' . $entityName . '/' . $templateName;
	}

	//                                                                      //
	//                        Back-End Crud Methods                         //
	//                                                                      //

	/**
	 * CRUD Listing page
	 *
	 * @param string    $entityName                 The Entity Name
	 * @param string    $entityRepositoryNamespace  The EntityRepository Namespace
	 * @param string    $entityViewNamespace        The Entity View Namespace
	 * @param array     $additionnalParameters      Some optional parameters for the template rendering
	 * @param array     $entities                   An optional list of entities to display [without querying]
	 * @return Response
	 */
	public function listEntities($entityName, $entityRepositoryNamespace, $entityViewNamespace, $additionnalParameters = array(), $entities = null)
	{
		$em = $this->getDoctrine()->getManager();

		if (!$entities) {
			$entities = $em->getRepository($entityRepositoryNamespace . ':' . $entityName)->findAll();
		}

		$stateForms = array();

		foreach ($entities as $entity) {
			$stateForm = $this->createEntityForm($entity->getId());
			$stateForms[$entity->getId()] = $stateForm->createView();
		}

		$parameters = array(
			'entities' => $entities,
			'state_forms' => $stateForms
		);

		if ($additionnalParameters) {
			$parameters = array_merge($parameters, $additionnalParameters);
		}

		return $this->render($this->getTemplate($entityViewNamespace, $entityName, 'list.html.twig'), $parameters);
	}

	/**
	 * CRUD Creation form page
	 *
	 * @param stdClass      $entity                 The Entity instance
	 * @param AbstractType  $form                   The Entity FormType
	 * @param string        $entityName             The Entity Name
	 * @param string        $entityViewNamespace    The Entity View Namespace
	 * @param array         $additionnalParameters  Some optional parameters for the template rendering
	 * @return Response
	 */
	public function renderCreationForm($entity, $form, $entityName, $entityViewNamespace, $additionnalParameters = array())
	{
		$form = $this->createForm($form, $entity);

		$parameters = array(
			'entity' => $entity,
			'form' => $form->createView()
		);

		if ($additionnalParameters) {
			$parameters = array_merge($parameters, $additionnalParameters);
		}

		return $this->render($this->getTemplate($entityViewNamespace, $entityName, 'new.html.twig'), $parameters);
	}

	/**
	 * CRUD Creation page
	 *
	 * @param Request       $request                The current request
	 * @param stdClass      $entity                 The Entity instance
	 * @param AbstractType  $form                   The Entity FormType
	 * @param string        $entityName             The Entity Name
	 * @param string        $entityViewNamespace    The Entity View Namespace
	 * @param string        $redirection            The route to redirect when the creation is done
	 * @param array         $additionnalParameters  Some optional parameters for the redirection
	 * @param callable      $preFlushCallback       An optional callback to call before the database query
	 * @param callable      $postFlushCallback      An optional callback to call after the database query
	 * @return Response
	 */
	public function createEntity($request, $entity, $form, $entityName, $entityViewNamespace, $redirection, $additionnalParameters = array(), $preFlushCallback = null, $postFlushCallback = null)
	{
		$form = $this->createForm($form, $entity);
		$form->handleRequest($request);

		if ($form->isValid()) {
			$em = $this->getDoctrine()->getManager();

			if ($preFlushCallback && is_callable($preFlushCallback)) {
				$preFlushCallback($entity, $em);
			}

			$em->persist($entity);
			$em->flush();

			if ($postFlushCallback && is_callable($postFlushCallback)) {
				$postFlushCallback($entity);
			}

			$this->addFlash('success', sprintf('L\'entité %s à bien été enregistrée.', $entity));

			$routeParams = array('id' => $entity->getId());

			if (array_key_exists('routeParams', $additionnalParameters)) {
				$routeParams = array_merge($routeParams, $additionnalParameters['routeParams']);
			}

			return $this->redirectToRoute($redirection, $routeParams);
		} else {
			$this->addFlash('danger', sprintf('L\'entité %s n\'a pas pu être enregistrée.', $entityName));
		}

		$parameters = array(
			'entity' => $entity,
			'form' => $form->createView()
		);

		if ($additionnalParameters) {
			$parameters = array_merge($parameters, $additionnalParameters);
		}

		return $this->render($this->getTemplate($entityViewNamespace, $entityName, 'new.html.twig'), $parameters);
	}

	/**
	 * CRUD Show page
	 *
	 * @param integer   $id                         The Entity id
	 * @param string    $entityName                 The Entity Name
	 * @param string    $entityRepositoryNamespace  The EntityRepository Namespace
	 * @param string    $entityViewNamespace        The Entity View Namespace
	 * @param array     $additionnalParameters      Some optional parameters for the redirection
	 * @return Response
	 */
	public function showEntity($id, $entityName, $entityRepositoryNamespace, $entityViewNamespace, $additionnalParameters = array())
	{
		$em = $this->getDoctrine()->getManager();

		if (array_key_exists('entity', $additionnalParameters)) {
			$entity = $additionnalParameters['entity'];
		} else {
			$entity = $em->getRepository($entityRepositoryNamespace . ':' . $entityName)->findOneNotDeleted($id);

			if (!$entity) {
				throw $this->createNotFoundException(sprintf('L\'entité %s est introuvable.', $entityName));
			}
		}

		$stateForm = $this->createEntityForm($id);
		$deleteForm = $this->createEntityForm($id);

		$parameters = array(
			'entity' => $entity,
			'state_form' => $stateForm->createView(),
			'delete_form' => $deleteForm->createView()
		);

		if ($additionnalParameters) {
			$parameters = array_merge($parameters, $additionnalParameters);
		}

		return $this->render($this->getTemplate($entityViewNamespace, $entityName, 'show.html.twig'), $parameters);
	}

	/**
	 * CRUD Update form page
	 *
	 * @param integer       $id                         The Entity id
	 * @param AbstractType  $form                   The Entity FormType
	 * @param string        $entityName                 The Entity Name
	 * @param string        $entityRepositoryNamespace  The EntityRepository Namespace
	 * @param string        $entityViewNamespace        The Entity View Namespace
	 * @param array         $additionnalParameters      Some optional parameters for the redirection
	 * @return Response
	 */
	public function renderEditForm($id, $form, $entityName, $entityRepositoryNamespace, $entityViewNamespace, $additionnalParameters = array())
	{
		$em = $this->getDoctrine()->getManager();

		$entity = $em->getRepository($entityRepositoryNamespace . ':' . $entityName)->findOneNotDeleted($id);

		if (!$entity) {
			throw $this->createNotFoundException(sprintf('L\'entité %s est introuvable.', $entityName));
		}

		$editForm = $this->createForm($form, $entity);

		$stateForm = $this->createEntityForm($id);
		$deleteForm = $this->createEntityForm($id);

		$parameters = array(
			'entity'      => $entity,
			'edit_form'   => $editForm->createView(),
			'state_form'  => $stateForm->createView(),
			'delete_form' => $deleteForm->createView()
		);

		if ($additionnalParameters) {
			$parameters = array_merge($parameters, $additionnalParameters);
		}

		return $this->render($this->getTemplate($entityViewNamespace, $entityName, 'edit.html.twig'), $parameters);
	}

	/**
	 * CRUD Update page
	 *
	 * @param Request       $request                    The current request
	 * @param integer       $id                         The Entity id
	 * @param AbstractType  $form                       The Entity FormType
	 * @param string        $entityName                 The Entity Name
	 * @param string        $entityRepositoryNamespace  The EntityRepository Namespace
	 * @param string        $entityViewNamespace        The Entity View Namespace
	 * @param string        $redirection                The route to redirect when the creation is done
	 * @param array         $additionnalParameters      Some optional parameters for the redirection
	 * @param callable      $preFlushCallback           An optional callback to call before the database query
	 * @param callable      $postFlushCallback          An optional callback to call after the database query
	 * @return Response
	 */
	public function updateEntity($request, $id, $form, $entityName, $entityRepositoryNamespace, $entityViewNamespace, $redirection, $additionnalParameters = array(), $preFlushCallback = null, $postFlushCallback = null)
	{
		$em = $this->getDoctrine()->getManager();

		$entity = $em->getRepository($entityRepositoryNamespace . ':' . $entityName)->findOneNotDeleted($id);

		if (!$entity) {
			throw $this->createNotFoundException(sprintf('L\'entité %s est introuvable.', $entityName));
		}

		$editForm = $this->createForm($form, $entity);
		$editForm->handleRequest($request);

		if ($editForm->isValid()) {
			if ($preFlushCallback && is_callable($preFlushCallback)) {
				$preFlushCallback($entity, $em);
			}

			$em->persist($entity);
			$em->flush();

			if ($postFlushCallback && is_callable($postFlushCallback)) {
				$postFlushCallback($entity);
			}

			$this->addFlash('success', sprintf('L\'entité %s à bien été mise à jour.', $entity));

			$routeParams = array('id' => $entity->getId());

			if (array_key_exists('routeParams', $additionnalParameters)) {
				$routeParams = array_merge($routeParams, $additionnalParameters['routeParams']);
			}

			return $this->redirectToRoute($redirection, $routeParams);
		} else {
			$this->addFlash('danger', sprintf('L\'entité %s n\'a pas pu être mise à jour.', $entity));
		}

		$stateForm = $this->createEntityForm($id);
		$deleteForm = $this->createEntityForm($id);

		$parameters = array(
			'entity'      => $entity,
			'edit_form'   => $editForm->createView(),
			'state_form'  => $stateForm->createView(),
			'delete_form' => $deleteForm->createView()
		);

		if ($additionnalParameters) {
			$parameters = array_merge($parameters, $additionnalParameters);
		}

		return $this->render($this->getTemplate($entityViewNamespace, $entityName, 'edit.html.twig'), $parameters);
	}

	/**
	 * CRUD Delete page
	 *
	 * @param Request   $request                    The current request
	 * @param integer   $id                         The Entity id
	 * @param string    $entityName                 The Entity Name
	 * @param string    $entityRepositoryNamespace  The EntityRepository Namespace
	 * @param string    $redirection                The route to redirect when the creation is done
	 * @param array     $additionnalParameters      Some optional parameters for the redirection
	 * @return Response
	 */
	public function deleteEntity($request, $id, $entityName, $entityRepositoryNamespace, $redirection, $additionnalParameters = array())
	{
		$form = $this->createEntityForm($id);
		$form->handleRequest($request);

		if ($form->isValid()) {
			$em = $this->getDoctrine()->getManager();
			$entity = $em->getRepository($entityRepositoryNamespace . ':' . $entityName)->findOneNotDeleted($id);

			if (!$entity) {
				throw $this->createNotFoundException(sprintf('L\'entité %s est introuvable.', $entityName));
			}

			$entityDisplayName = $entity->__toString();

			$em->remove($entity);
			$em->flush();

			$this->addFlash('success', sprintf('L\'entité %s à bien été supprimée.', $entityDisplayName));
		} else {
			$this->addFlash('danger', sprintf('L\'entité %s n\'a pas pu être supprimée.', $entityDisplayName));
		}

		$routeParams = array();

		if (array_key_exists('routeParams', $additionnalParameters)) {
			$routeParams = $additionnalParameters['routeParams'];
		}

		return $this->redirectToRoute($redirection, $routeParams);
	}

	/**
	 * Creates a form to do any operation on any entity, identified by its id.
	 *
	 * @param mixed $id The entity id
	 * @return \Symfony\Component\Form\Form The form
	 */
	private function createEntityForm($id)
	{
		return $this
			->createFormBuilder(array('id' => $id))
			->add('id', 'hidden')
			->getForm()
		;
	}
}
