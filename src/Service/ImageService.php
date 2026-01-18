<?php

namespace App\Service;

use App\Document\Image;
use Doctrine\ODM\MongoDB\DocumentManager;

class ImageService
{
    public function __construct(
        private DocumentManager $dm,
        private string          $projectDir,
    )
    {
    }

    public function parseImage(
        string $url,
        int    $minWidth,
        int    $minHeight,
        string $text
    ): array
    {
        $html = @file_get_contents($url);

        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        if (!$html) {
            return [];
        }
        $images = $dom->getElementsByTagName('img');

        $result = [];

        foreach ($images as $img) {
            $src = $img->getAttribute('src');
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

            $this->dm->persist($imageDoc);
            $result[] = ['filename' => $filename];
        }

        $this->dm->flush();

        return $result;
    }

    private function makeAbsoluteUrl(string $src, string $base): string
    {
        if (parse_url($src, PHP_URL_SCHEME) === null) {
            $baseParts = parse_url($base);
            $src = $baseParts['scheme'] . '://' . $baseParts['host'] . '/' . ltrim($src, '/');
        }
        return $src;
    }

    private function processImage(string $content, ?string $text): ?string
    {
        $image = @imagecreatefromstring($content);
        if (!$image) {
            return null;
        }

        $ratio = 200 / imagesy($image);
        $resized = imagescale($image, (int)(imagesx($image) * $ratio), 200);

        $x = max(0, (imagesx($resized) - 200) / 2);
        $y = max(0, (imagesy($resized) - 200) / 2);

        $cropped = imagecrop($resized, [
            'x' => (int)$x,
            'y' => (int)$y,
            'width' => 200,
            'height' => 200
        ]);

        if (!$cropped) {
            return null;
        }

        if ($text) {
            $color = imagecolorallocate($cropped, 255, 255, 255);
            imagestring($cropped, 5, 5, 5, $text, $color);
        }

        $path = $this->projectDir . '/public/uploads';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $filename = 'uploads/' . uniqid('', true) . '.jpg';
        imagejpeg($cropped, $this->projectDir . '/public/' . $filename);

        return $filename;
    }
}