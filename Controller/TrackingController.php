<?php

declare(strict_types=1);

namespace RevisionTen\Forms\Controller;

use Doctrine\ORM\EntityManagerInterface;
use RevisionTen\Forms\Entity\FormSubmission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class TrackingController.
 */
class TrackingController extends AbstractController
{
    /**
     * @Route("/forms/pixel/{trackingToken}/pixel.png", name="forms_tracking_pixel")
     *
     * @param KernelInterface        $kernel
     * @param EntityManagerInterface $entityManager
     * @param string                 $trackingToken
     *
     * @return Response
     */
    public function trackingPixel(KernelInterface $kernel, EntityManagerInterface $entityManager, string $trackingToken): Response
    {
        $formSubmission = $entityManager->getRepository(FormSubmission::class)->findOneBy(['trackingToken' => $trackingToken]);

        if (null !== $formSubmission) {
            $formSubmission->setOpened(true);

            $entityManager->persist($formSubmission);
            $entityManager->flush();
            $entityManager->clear();
        }

        $file = $kernel->locateResource('@FormsBundle/Resources/pixel.png');
        $response = new BinaryFileResponse($file);

        $response->headers->set('Content-Disposition', 'inline; filename="pixel.png"');
        $response->headers->set('Content-Type', 'image/png');

        return $response;
    }
}
