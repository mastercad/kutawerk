<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\NewsPost;
use App\Repository\NewsPostRepository;
use App\Service\PreviewClock;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/news', name: 'news_')]
final class NewsController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(NewsPostRepository $repository, PreviewClock $clock): Response
    {
        return $this->render('pages/news.html.twig', ['current_posts' => $repository->published($clock->now()),'previewAt'=>$clock->isPreview()?$clock->now():null]);
    }

    #[Route('/{slug}', name: 'article', methods: ['GET'])]
    public function article(#[MapEntity(mapping: ['slug' => 'slug'])] NewsPost $post, PreviewClock $clock): Response
    {
        if (!$post->isVisibleAt($clock->now())) throw $this->createNotFoundException();
        $galleries = [];
        foreach ($post->getGalleryImages() as $image) $galleries[$image->getGalleryKey()][] = $image;
        $parts = preg_split('/\[\[NEWS_GALLERY:([a-zA-Z0-9-]+)\]\]/', $post->getContent(), -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$post->getContent()];
        $contentBlocks = [];
        foreach ($parts as $index => $part) $contentBlocks[] = $index % 2 === 0 ? ['type' => 'html', 'content' => $part] : ['type' => 'gallery', 'images' => $galleries[$part] ?? []];
        $placed = array_values(array_filter($parts, static fn (string $part, int $index): bool => $index % 2 === 1, ARRAY_FILTER_USE_BOTH));
        foreach ($galleries as $key => $images) if (!in_array($key, $placed, true)) $contentBlocks[] = ['type' => 'gallery', 'images' => $images];
        return $this->render('pages/news_post.html.twig', ['post' => $post, 'contentBlocks' => $contentBlocks]);
    }
}
