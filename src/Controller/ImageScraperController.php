<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\DTO\UrlDTO;
use App\Service\ImageScraperService;

class ImageScraperController extends AbstractController
{

    private ImageScraperService $imageScraperService;

    public function __construct(ImageScraperService $imageScraperService)
    {
        $this->imageScraperService = $imageScraperService;
    }

    /*#[Route('/image/scraper', name: 'app_image_scraper')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ImageScraperController.php',
        ]);
    }*/

    #[Route('/start', name: 'start_image_scraper')]
    public function start(Request $request, ValidatorInterface $validator): Response|null
    {
        /** @var string|null $url */
        $url = $request->request->get('url');

        if (isset($url)) {
            $urlDTO = new UrlDTO($url);
            $errors = $validator->validate($urlDTO);

            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                $errorsString = implode(' ', $errorMessages);
                $request->getSession()->getFlashBag()->add('error', $errorsString);
            } else {
                $url = rtrim($url, '/');
                return $this->redirectToRoute('result_image_scraper', ['url' => $url]);
            }
        }

        return $this->render('start.html.twig');
    }

    #[Route('/result', name: 'result_image_scraper')]
    public function result(Request $request): Response
    {
        /** @var string|null $url */
        $url = $request->query->get('url');

        /** @var string[] $images */
        $images = $this->imageScraperService->parseImagesFromUrl($url);

        /** @var float $fileSize */
        $fileSize = $this->imageScraperService->findFileSize($images);

        /** @var string[] $images */
        $images = $this->imageScraperService->rebuildArray($images);

        return $this->render('result.html.twig', ['url' => $url, 'images' => $images, 'fileSize' => $fileSize]);
    }
}
