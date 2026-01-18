<?php

namespace App\Controller;

use App\Data\Request\ImageRequestDto;
use App\Document\Image;
use App\Service\ImageService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(
        DocumentManager $dm
    ): Response
    {
        $images = $dm->getRepository(Image::class)->findAll();

        return $this->render('index.html.twig', [
            'images' => $images
        ]);
    }

    #[Route('/parse', name: 'parse_images', methods: ['POST'])]
    public function parse(
        #[MapRequestPayload] ImageRequestDto $imageRequestDto,
        ImageService                         $imageService
    ): JsonResponse
    {
        if (!@file_get_contents($imageRequestDto->url)) {
            return $this->json(['error' => 'Невозможно загрузить страницу'], 400);
        }

        $result = $imageService->parseImage(
            $imageRequestDto->url,
            $imageRequestDto->minWidth,
            $imageRequestDto->minHeight,
            $imageRequestDto->text
        );

        return $this->json($result);
    }
}
