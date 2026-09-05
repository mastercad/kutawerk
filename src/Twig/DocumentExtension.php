<?php
declare(strict_types=1);

namespace App\Twig;

use App\Repository\DocumentVersionRepository;
use App\Service\PreviewClock;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class DocumentExtension extends AbstractExtension
{
    public function __construct(private DocumentVersionRepository $documents, private PreviewClock $clock) {}
    public function getFunctions(): array { return [new TwigFunction('managed_document',[$this,'document'])]; }
    public function document(string $key): mixed { return $this->documents->findCurrent($key,$this->clock->now()); }
}
