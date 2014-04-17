<?php

/*
 * This file is part of the OpenMiamMiam project.
 *
 * (c) Isics <contact@isics.fr>
 *
 * This source file is subject to the AGPL v3 license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Isics\Bundle\OpenMiamMiamBundle\Controller\Admin\Association;

use Isics\Bundle\OpenMiamMiamBundle\Controller\Admin\Association\BaseController;
use Isics\Bundle\OpenMiamMiamBundle\Entity\Association;
use Isics\Bundle\OpenMiamMiamBundle\Entity\Payment;
use Isics\Bundle\OpenMiamMiamBundle\Entity\PaymentAllocation;
use Isics\Bundle\OpenMiamMiamBundle\Entity\SalesOrder;
use Isics\Bundle\OpenMiamMiamBundle\Entity\Comment;
use Isics\Bundle\OpenMiamMiamBundle\Form\Type\CommentType;
use Isics\Bundle\OpenMiamMiamUserBundle\Entity\User;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ConsumerController extends BaseController
{
    /**
     * @param Association $association
     * @param User        $consumer
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function secureConsumer(Association $association, User $consumer)
    {
        if (null === $this->get('open_miam_miam.consumer_manager')->getSubscription($association, $consumer)) {
            throw $this->createNotFoundException('Invalid consumer for association');
        }
    }

    /**
     * Edit a consumer
     *
     * @ParamConverter("association", class="IsicsOpenMiamMiamBundle:Association", options={"mapping": {"associationId": "id"}})
     * @ParamConverter("consumer", class="IsicsOpenMiamMiamUserBundle:User", options={"mapping": {"consumerId": "id"}})
     * 
     * @param Association $association
     * @param User        $consumer
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return Response
     */
    public function editAction(Association $association, User $consumer)
    {
        $this->secure($association);
        $this->secureConsumer($association, $consumer);

        $comment = new Comment;
        $form = $this->createForm(new CommentType, $comment);

        $request = $this->getRequest();
        if ($request->getMethod() == 'POST')
        {
            $form->bind($request);
            if ($form->isValid())
            {
                $em = $this->getDoctrine()->getManager();
                $comment->setWritingDate(new \Datetime);
                $comment->setUser($consumer);
                $comment->setWriter($this->get('security.context')->getToken()->getUser());
                $comment->setIsProcessed(false);
                $em->persist($comment);
                $em->flush();

                return $this->redirect($this->generateUrl('open_miam_miam.admin.association.consumer.edit', array(
                    'associationId' => $association->getId(),
                    'consumerId'    => $consumer->getId(),
                )));
            }
        }

        return $this->render('IsicsOpenMiamMiamBundle:Admin\Association\Consumer:listComments.html.twig', array(
            'association' => $association,
            'consumer'    => $consumer,
            'form'        => $form->createView(),
        ));
    }

    /**
     * Process a comment
     *
     * @ParamConverter("association", class="IsicsOpenMiamMiamBundle:Association", options={"mapping": {"associationId": "id"}})
     * @ParamConverter("consumer", class="IsicsOpenMiamMiamUserBundle:User", options={"mapping": {"consumerId": "id"}})
     * @ParamConverter("comment", class="IsicsOpenMiamMiamBundle:Comment", options={"mapping": {"commentId": "id"}})
     * 
     * @param Association $association
     * @param User        $consumer
     * @param Comment     $comment
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return Response
     */
    public function processCommentAction(Association $association, User $consumer, Comment $comment)
    {
        $this->secure($association);
        $this->secureConsumer($association, $consumer);

        $em = $this->getDoctrine()->getManager();
        $comment->setIsProcessed(true);
        $em->persist($comment);
        $em->flush();

        return $this->redirect($this->getRequest()->headers->get('referer'));
    }

    /**
     * List consumers
     *
     * @param Request     $request
     * @param Association $association
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return Response
     */
    public function listAction(Request $request, Association $association)
    {
        $this->secure($association);

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter(
            $this->getDoctrine()->getRepository('IsicsOpenMiamMiamBundle:Subscription')
                ->getForAssociationQueryBuilder($association)
                ->getQuery()
        ));
        $pagerfanta->setMaxPerPage($this->container->getParameter('open_miam_miam.association.pagination.consumers'));

        try {
            $pagerfanta->setCurrentPage($request->query->get('page', 1));
        } catch (NotValidCurrentPageException $e) {
            throw $this->createNotFoundException();
        }

        return $this->render('IsicsOpenMiamMiamBundle:Admin\Association\Consumer:list.html.twig', array(
            'association'   => $association,
            'subscriptions' => $pagerfanta
        ));
    }

    /**
     * List anonymous payments
     *
     * @param Request     $request
     * @param Association $association
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @return Response
     */
    public function listAnonymousPaymentsAction(Request $request, Association $association)
    {
        $this->secure($association);

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter(
            $this->getDoctrine()->getRepository('IsicsOpenMiamMiamBundle:Payment')
                ->getForConsumerAndAssociationQueryBuilder($association)

                ->getQuery()
        ));
        $pagerfanta->setMaxPerPage($this->container->getParameter('open_miam_miam.association.pagination.consumer_payments'));

        try {
            $pagerfanta->setCurrentPage($request->query->get('page', 1));
        } catch (NotValidCurrentPageException $e) {
            throw $this->createNotFoundException();
        }

        return $this->render('IsicsOpenMiamMiamBundle:Admin\Association\Consumer:listPayments.html.twig', array(
            'association' => $association,
            'payments'    => $pagerfanta
        ));
    }

    /**
     * List consumer payments
     *
     * @ParamConverter("consumer", class="IsicsOpenMiamMiamUserBundle:User", options={"mapping": {"consumerId": "id"}})
     *
     * @param Request     $request
     * @param Association $association
     * @param User        $consumer
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @return Response
     */
    public function listPaymentsAction(Request $request, Association $association, User $consumer)
    {
        $this->secure($association);
        $this->secureConsumer($association, $consumer);

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter(
            $this->getDoctrine()->getRepository('IsicsOpenMiamMiamBundle:Payment')
                ->getForConsumerAndAssociationQueryBuilder($association, $consumer)
                ->getQuery()
        ));
        $pagerfanta->setMaxPerPage($this->container->getParameter('open_miam_miam.association.pagination.consumer_payments'));

        try {
            $pagerfanta->setCurrentPage($request->query->get('page', 1));
        } catch (NotValidCurrentPageException $e) {
            throw $this->createNotFoundException();
        }

        return $this->render('IsicsOpenMiamMiamBundle:Admin\Association\Consumer:listPayments.html.twig', array(
            'association' => $association,
            'consumer'    => $consumer,
            'payments'    => $pagerfanta
        ));
    }

    /**
     * List allocations for payment
     *
     * @ParamConverter("payment", class="IsicsOpenMiamMiamBundle:Payment", options={"mapping": {"paymentId": "id"}})
     *
     * @param Association $association Association
     * @param Payment     $payment     Payment
     *
     * @return Response
     */
    public function listPaymentAllocationsAction(Association $association, Payment $payment)
    {
        $this->secure($association);

        return $this->render('IsicsOpenMiamMiamBundle:Admin\Association\Consumer:listPaymentAllocations.html.twig', array(
            'association' => $association,
            'payment'     => $payment,
            'allocations' => $payment->getPaymentAllocations(),
            'consumer'    => $payment->getUser()
        ));
    }
}
