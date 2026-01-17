<?php

namespace App\Controller;

use App\Document\Image;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(DocumentManager $dm)
    {
//        $images = [];
        $images = $dm->getRepository(Image::class)->findAll();

        return $this->render('index.html.twig', [
            'images' => $images
        ]);
    }

    #[Route('/parse', name: 'parse_images', methods: ['POST'])]
    public function parse(Request $request, DocumentManager $dm): JsonResponse
    {
        $url = $request->request->get('url');
        $minWidth = (int)$request->request->get('min_width');
        $minHeight = (int)$request->request->get('min_height');
        $text = $request->request->get('text');

        if (!$url) {
            return $this->json(['error' => 'Введите URL страницы'], 400);
        }

        $html = @file_get_contents($url);
        if (!$html) {
            return $this->json(['error' => 'Невозможно загрузить страницу'], 400);
        }

        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $imagesTags = $dom->getElementsByTagName('img');

        $result = [];

        foreach ($imagesTags as $imgTag) {
            $src = $imgTag->getAttribute('src');
            if (!$src) continue;

            $src = $this->makeAbsoluteUrl($src, $url);

            $imageContent = @file_get_contents($src);
            if (!$imageContent) continue;

            $size = @getimagesizefromstring($imageContent);
            if (!$size) continue;

            [$width, $height] = $size;
            if ($width < $minWidth || $height < $minHeight) continue;

            $filename = $this->processImage($imageContent, $text);

            $imageDoc = new Image();
            $imageDoc->setFilename($filename);
            $imageDoc->setOriginalUrl($src);
            $imageDoc->setWidth(200);
            $imageDoc->setHeight(200);
            $imageDoc->setText($text);

            $dm->persist($imageDoc);
            $dm->flush();

            $result[] = ['filename' => $filename];
        }

        return $this->json($result);
    }

    private function makeAbsoluteUrl(string $src, string $base): string
    {
        if (parse_url($src, PHP_URL_SCHEME) === null) {
            $baseParts = parse_url($base);
            $src = $baseParts['scheme'] . '://' . $baseParts['host'] . '/' . ltrim($src, '/');
        }
        return $src;
    }

    private function processImage(string $content, string $text): string
    {
        $image = imagecreatefromstring($content);

        if (!$image) return '';

        $ratio = 200 / imagesy($image);
        $newWidth = (int)(imagesx($image) * $ratio);
        $resized = imagescale($image, $newWidth, 200);

        $cropped = imagecrop($resized, ['x' => 0, 'y' => 0, 'width' => 200, 'height' => 200]);

        $color = imagecolorallocate($cropped, 255, 255, 255);
        imagestring($cropped, 5, 5, 5, $text, $color);

        if (!is_dir('public/uploads')) mkdir('public/uploads', 0777, true);
        $filename = 'uploads/' . uniqid() . '.jpg';
        imagejpeg($cropped, 'public/' . $filename);

        imagedestroy($image);
        imagedestroy($resized);
        imagedestroy($cropped);

        return $filename;
    }
}
