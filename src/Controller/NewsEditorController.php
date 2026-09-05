<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\NewsPost;
use App\Entity\NewsGalleryImage;
use App\Entity\User;
use App\Repository\NewsPostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/editor/news', name: 'editor_news')]
#[IsGranted('ROLE_USER')]
final class NewsEditorController extends AbstractController
{
    #[Route('', name: '', methods: ['GET', 'POST'])]
    public function index(Request $request, NewsPostRepository $repository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->hasPermission(User::PERMISSION_NEWS)) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('news-save', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }

            $id = $request->request->getInt('id');
            $post = $id > 0 ? $repository->find($id) : new NewsPost();
            if (!$post instanceof NewsPost) {
                throw $this->createNotFoundException();
            }

            $title = trim((string) $request->request->get('title'));
            $plainContent = (string) preg_replace('/\[\[(?:NEWS_GALLERY(?::[a-zA-Z0-9-]+)?|NEWS_IMAGE:\d+)\]\]/', '', (string) $request->request->get('content'));
            if ($title === '' || trim(strip_tags($plainContent)) === '') {
                $this->addFlash('error', 'Bitte geben Sie eine Überschrift und einen Beitragstext ein.');
                return $this->redirectToRoute('editor_news');
            }
            $visibleFrom = $this->dateTime($request, 'visible_from');
            $visibleUntil = $this->dateTime($request, 'visible_until');
            if ($visibleFrom && $visibleUntil && $visibleUntil < $visibleFrom) {
                $this->addFlash('error', '„Anzeigen bis“ darf nicht vor „Anzeigen ab“ liegen.');
                return $this->redirectToRoute('editor_news');
            }
            $image = $request->files->get('image');
            if ($image instanceof UploadedFile) {
                $allowedImages = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                $mimeType = (string) $image->getMimeType();
                if (!isset($allowedImages[$mimeType]) || $image->getSize() > 5_000_000) {
                    $this->addFlash('error', 'Das Bild konnte nicht verwendet werden. Bitte wählen Sie ein JPG-, PNG- oder WebP-Bild mit höchstens 5 MB.');
                    return $this->redirectToRoute('editor_news');
                }
                $filename = sprintf('%s-%s.%s', date('YmdHis'), bin2hex(random_bytes(4)), $allowedImages[$mimeType]);
                $image->move($this->getParameter('kernel.project_dir').'/public/uploads/news', $filename);
                $post->setImagePath('/uploads/news/'.$filename);
            }
            $content = (string) $request->request->get('content');
            foreach ((array) $request->files->all('content_images') as $index => $contentImage) {
                $token = '[[NEWS_IMAGE:'.(int) $index.']]';
                if (!str_contains($content, $token)) continue;
                if (!$contentImage instanceof UploadedFile || ($path = $this->storeImage($contentImage)) === null) {
                    $this->addFlash('error', 'Ein Bild im Beitrag war ungültig. Erlaubt sind JPG, PNG und WebP bis 5 MB.');
                    return $this->redirectToRoute('editor_news');
                }
                $content = str_replace($token, '<figure class="news-inline-image"><img src="'.$path.'" alt=""></figure>', $content);
            }
            $post->setTitle($title)->setSlug($this->uniqueSlug($title, $post, $repository))
                ->setContent($content)
                ->setExcerpt(trim((string) $request->request->get('excerpt')) ?: null)
                ->setContentIsHtml($request->request->getBoolean('content_is_html'))
                ->setPublishedAt(new \DateTimeImmutable((string) $request->request->get('published_at').' 12:00:00'))
                ->setVisibleFrom($visibleFrom)->setVisibleUntil($visibleUntil)
                ->setPublished($request->request->getBoolean('published'))->setAuthor($user);
            $deleteIds = array_map('intval', (array) $request->request->all('delete_gallery'));
            $captions = (array) $request->request->all('gallery_caption');
            $positions = (array) $request->request->all('gallery_position');
            $groups = (array) $request->request->all('gallery_group');
            $replacements = $request->files->all('gallery_replace');
            foreach ($post->getGalleryImages()->toArray() as $galleryImage) {
                $galleryId = $galleryImage->getId();
                if ($galleryId !== null && in_array($galleryId, $deleteIds, true)) {
                    $post->removeGalleryImage($galleryImage);
                    continue;
                }
                if ($galleryId !== null) {
                    $galleryImage->setCaption(isset($captions[$galleryId]) ? (string) $captions[$galleryId] : null)
                        ->setPosition(max(0, (int) ($positions[$galleryId] ?? $galleryImage->getPosition())))
                        ->setGalleryKey($this->galleryKey((string) ($groups[$galleryId] ?? $galleryImage->getGalleryKey())));
                    $replacement = $replacements[$galleryId] ?? null;
                    if ($replacement instanceof UploadedFile && $replacement->getError() !== UPLOAD_ERR_NO_FILE) {
                        $replacementPath = $this->storeImage($replacement);
                        if ($replacementPath === null) {
                            $this->addFlash('error', 'Das Ersatzbild war ungültig. Erlaubt sind JPG, PNG und WebP bis 5 MB.');
                            return $this->redirectToRoute('editor_news');
                        }
                        $galleryImage->setImagePath($replacementPath);
                    }
                }
            }
            $galleryUploads = $request->files->all('gallery_images');
            $uploadCount = array_sum(array_map(static fn ($uploads): int => is_array($uploads) ? count($uploads) : 0, $galleryUploads));
            if ($uploadCount > 20) {
                $this->addFlash('error', 'Bitte laden Sie höchstens 20 Galeriebilder gleichzeitig hoch.');
                return $this->redirectToRoute('editor_news');
            }
            $nextPosition = count($post->getGalleryImages());
            $newGalleryImages = [];
            $skippedUploads = (array) $request->request->all('skip_gallery_new');
            foreach ($galleryUploads as $galleryKey => $uploads) foreach ((array) $uploads as $uploadIndex => $galleryUpload) {
                $galleryKey = $this->galleryKey((string) $galleryKey);
                if (in_array((int) $uploadIndex, array_map('intval', (array) ($skippedUploads[$galleryKey] ?? [])), true)) continue;
                if (!$galleryUpload instanceof UploadedFile || $galleryUpload->getError() === UPLOAD_ERR_NO_FILE) continue;
                $path = $this->storeImage($galleryUpload);
                if ($path === null) {
                    $this->addFlash('error', 'Mindestens ein Galeriebild war ungültig. Erlaubt sind JPG, PNG und WebP bis 5 MB.');
                    return $this->redirectToRoute('editor_news');
                }
                $newGalleryImages[$galleryKey][(int) $uploadIndex] = (new NewsGalleryImage())->setImagePath($path)->setPosition($nextPosition++)->setGalleryKey($galleryKey);
                $post->addGalleryImage($newGalleryImages[$galleryKey][(int) $uploadIndex]);
            }
            $byId = [];
            foreach ($post->getGalleryImages() as $galleryImage) if ($galleryImage->getId() !== null) $byId[$galleryImage->getId()] = $galleryImage;
            foreach ((array) $request->request->all('gallery_order') as $galleryKey => $encodedOrder) foreach (array_filter(explode(',', (string) $encodedOrder)) as $position => $token) {
                [$kind, $value] = array_pad(explode(':', $token, 2), 2, '');
                $orderedImage = $kind === 'id' ? ($byId[(int) $value] ?? null) : ($newGalleryImages[(string) $galleryKey][(int) $value] ?? null);
                if ($orderedImage instanceof NewsGalleryImage) $orderedImage->setPosition($position)->setGalleryKey($this->galleryKey((string) $galleryKey));
            }
            $entityManager->persist($post);
            $entityManager->flush();
            $this->addFlash('success', 'News-Beitrag gespeichert.');

            return $this->redirectToRoute('editor_news');
        }

        return $this->render('editor/news.html.twig', ['posts' => $repository->findBy([], ['publishedAt' => 'DESC'])]);
    }

    private function storeImage(UploadedFile $image): ?string
    {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = (string) $image->getMimeType();
        if (!isset($allowed[$mime]) || $image->getSize() > 5_000_000) return null;
        $filename = sprintf('%s-%s.%s', date('YmdHis'), bin2hex(random_bytes(6)), $allowed[$mime]);
        $image->move($this->getParameter('kernel.project_dir').'/public/uploads/news', $filename);
        return '/uploads/news/'.$filename;
    }

    private function dateTime(Request $request, string $key): ?\DateTimeImmutable
    {
        $value = trim((string) $request->request->get($key));
        return $value === '' ? null : new \DateTimeImmutable($value, new \DateTimeZone('Europe/Berlin'));
    }

    private function uniqueSlug(string $title, NewsPost $post, NewsPostRepository $repository): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title) ?: '';
        $base = trim(strtolower((string) preg_replace('/[^a-z0-9]+/', '-', $ascii)), '-') ?: 'news';
        $slug = $base;
        $suffix = 2;
        while (($existing = $repository->findOneBy(['slug' => $slug])) !== null && $existing->getId() !== $post->getId()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function galleryKey(string $value): string
    {
        return preg_match('/^[a-zA-Z0-9-]{1,36}$/', $value) ? $value : 'main';
    }
}
