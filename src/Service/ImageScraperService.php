<?php

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ImageScraperService
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @return string[]
     */
    public function parseImagesFromUrl(string $url): array
    {
        /** @var ResponseInterface $response */
        $response = $this->client->request('GET', $url);

        /** @var string $content */
        $content = $response->getContent();

        /** @var Crawler $crawler */
        $crawler = new Crawler($content);

        /** @var string[] $images */
        $images = $crawler->filter('img')->extract(['src']);

        /** @var string[] backgroundImages */
        $backgroundImages = $crawler->filterXPath('//div[@style]')->extract(['style']);

        $newBackgroundImages = [];

        foreach ($backgroundImages as $backgroundImage) {
            $explodedArray = explode(';', $backgroundImage);
            $newBackgroundImages = array_merge($newBackgroundImages, $explodedArray);
        }

        foreach ($newBackgroundImages as $key => $backgroundImage) {
            if (strpos($backgroundImage, 'background:') === false && strpos($backgroundImage, 'background-image:') === false) {
                unset($newBackgroundImages[$key]);
            }
        }

        foreach ($newBackgroundImages as $imageString) {
            if (preg_match('/url\(\'(.*?)\'\)/', $imageString, $matches)) {
                $images[] = $matches[1];
            }
        }

        foreach ($newBackgroundImages as $imageString) {
            if (preg_match('/url\("([^"]+)"\)/', $imageString, $matches)) {
                $images = $matches[1];
            }
        }

        $images = $this->checkFullPath($url, $images);

        return $images;
    }

    /**
     * @param string[] $images
     * @return string[]
     */
    private function checkFullPath(string $url, array $images): array
    {
        $newImages = [];

        foreach ($images as $image) {
            if (strpos($image, 'http') !== 0) {
                if (substr($image, 0, 1) === '/') {
                    $newImages[] = $url . $image;
                } else {
                    $newImages[] = $url . '/' . $image;
                }
            } else {
                $newImages[] = $image;
            }
        }

        return $newImages;
    }

    /**
     * @param string[] $images
     */
    public function findFileSize(array &$images): float
    {
        /** @var float $sizeAllFiles */
        $sizeAllFiles = 0;

        foreach ($images as $key => $image) {
            /** @var ResponseInterface $response */
            $response = $this->client->request('GET', $image);
            if ($response->getStatusCode() >= 400) {
                unset($images[$key]);
                continue;
            } else {
                /** @var string $imageContent */
                $imageContent = $response->getContent();

                /** @var string $tempFile */
                $tempFile = tempnam(sys_get_temp_dir(), 'image');
                file_put_contents($tempFile, $imageContent);
                $sizeAllFiles += $this->getFileSize($tempFile);
                unlink($tempFile);
            }
        }

        return $sizeAllFiles;
    }

    private function getFileSize(string $filePath): float
    {
        $fileSizeInBytes = filesize($filePath);
        $fileSizeInMegabytes = $fileSizeInBytes / (1024 * 1024);

        return round($fileSizeInMegabytes, 2);
    }

    /**
     * @param string[] $images
     * @return string[]
     */
    public function rebuildArray(array $images): array
    {
        $i = 1;
        $j = 0;
        $newImages = [];

        foreach ($images as $image) {
            $newImages[$j][] = $image;

            if ($i % 4 == 0) {
                $j++;
            }
            $i++;
        }

        return $newImages;
    }
}
