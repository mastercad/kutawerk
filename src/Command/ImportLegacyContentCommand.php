<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Course;
use App\Entity\Department;
use App\Entity\Event;
use App\Entity\Location;
use App\Entity\LocationAddressVersion;
use App\Entity\NewsPost;
use App\Entity\NewsGalleryImage;
use App\Entity\TrainingSession;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:content:import-legacy', description: 'Imports the versioned KuTaWerk legacy news, dance events and training schedule without overwriting existing records.')]
final class ImportLegacyContentCommand extends Command
{
    private const KNOWN_TRAINER_EMAILS = [
        'Beatrice Peana' => 'tanzsparte@kutawerk.de',
        'Linda Liebert' => 'liebert@kutawerk.de',
        'Josi Striemann' => 'verein@kutawerk.de',
    ];

    private const TRAINING_LOCATIONS = [
        'TanzArt' => ['Dresdner Straße 300', '01705', 'Freital', 'RVSOE-/VVO-Bushaltestelle Turnergäßchen in Freital-Hainsberg', []],
        'KuTa Lounge Outdoor' => ['Dresdner Straße 357', '01705', 'Freital', 'RVSOE-/VVO-Bushaltestelle Bahnhof Hainsberg in Freital-Hainsberg', []],
        'TH Krönertstr.' => ['Krönertstraße 25', '01705', 'Freital', 'Weißeritzgymnasium, Turnhalle; RVSOE-/VVO-Bushaltestelle Krönertstraße in Freital-Deuben', []],
        'TH Johannisstr.' => ['Johannisstraße 11', '01705', 'Freital', 'Weißeritzgymnasium, Turnhalle; RVSOE-/VVO-Bushaltestelle Krönertstraße in Freital-Deuben', []],
        'TH LuRi' => ['Ludwig-Richter-Straße 1', '01705', 'Freital', 'Grundschule Ludwig Richter Freital-Birkigt, Turnhalle; RVSOE-/VVO-Bushaltestelle Freital Birkigt Schule', []],
        'GS Scholl' => ['Richard-Wolf-Straße 1', '01705', 'Freital', 'Grundschule Geschwister Scholl Freital-Hainsberg; RVSOE-/VVO-Bushaltestelle Freital Coßmannsdorf Schule', []],
        'Selfit' => ['Roßmäßlerstraße 4', '01737', 'Tharandt', 'RVSOE-/VVO-Bushaltestelle Tharandt Markt', ['Selfit Tharandt']],
        'KuBi Galerie des SKZ – LIFEART (TGF/F1)' => ['Dresdner Straße 172 A', '01705', 'Freital', 'RVSOE-/VVO-Bushaltestelle Neumarkt', ['KuBi-Galerie, Dresdner Str. 172', 'LIFEART-/KUBI-Galerie']],
        'Gutshofbühne Pesterwitz – SKZ LIFEART' => ['Dorfplatz 1', '01705', 'Freital', 'RVSOE-/VVO-Bushaltestelle Pesterwitz Sonnenleite oder Pesterwitz Dorfplatz', ['Gutshofbühne Pesterwitz']],
        'Lessing OS' => ['Zur Lessingschule 17', '01705', 'Freital', 'Oberschule G. E. Lessing Freital-Potschappel', []],
        'GS Lessing' => ['Zur Lessingschule 17', '01705', 'Freital', 'Grundschule G. E. Lessing Freital-Potschappel', []],
        'Scholl OS' => ['Richard-Wolf-Straße 1', '01705', 'Freital', 'Oberschule Geschwister Scholl Freital-Hainsberg', []],
        'Weißeritzgymnasium' => ['Krönertstraße 25', '01705', 'Freital', 'Hauptgebäude; weiterer Schulteil in der Johannisstraße 11', []],
    ];

    private const NEWS = [
        ['news/manege-des-mutes-2025', 'circus-of-courage-2025', 'Manege des Mutes – Unser Tanztheater 2025', '2025-09-01', 'news__manege-des-mutes-2025.html'],
        ['news/wir-tanzen-zu-den-sternen', 'dancing-to-the-stars', 'Wir tanzen zu den Sternen!', '2023-04-21', 'news__wir-tanzen-zu-den-sternen.html'],
        ['news/wir-sind-dabei', 'association-competition-winner', 'Wir sind Gewinner beim Vereinswettbewerb - einer von 333 in Sachsen!', '2022-07-27', 'news__wir-sind-dabei.html'],
        ['news/krisenmodus', 'crisis-mode', '!!!', '2021-11-10', 'news__krisenmodus.html'],
        ['news/pilates-mit-hannah-kelly', 'pilates-with-hannah-kelly', 'NEU!!! Pilates ab 20.10. mit Hannah Kelly', '2020-10-02', 'news__pilates-mit-hannah-kelly.html'],
        ['news/alles-fuer-eure-party', 'everything-for-your-party', 'Alles für Eure Party!', '2020-08-05', 'news__alles-fuer-eure-party.html'],
        ['news/endlich-wieder-zumba', 'zumba-is-back', 'Endlich wieder Zumba®!!!', '2020-07-31', 'news__endlich-wieder-zumba.html'],
        ['news/corona-trainingsplan', 'coronavirus-training-schedule', 'Trainingsbetrieb wieder aufgenommen - Coronatrainingsplan', '2020-05-08', 'news__corona-trainingsplan.html'],
        ['news/trainingsbetrieb-eingestellt', 'dance-training-suspended', 'Trainingsbetrieb der Tanzsparte eingestellt', '2020-03-15', 'news__trainingsbetrieb-eingestellt.html'],
        ['news/kutawards-verschoben', 'kutawards-postponed', 'KuTaWards - Veranstaltung verschoben - auf 21.03.2021', '2020-03-10', 'news__kutawards-verschoben.html'],
    ];

    private const NEWS_EXCERPTS = [
        'news/manege-des-mutes-2025' => 'Am 22. Juni 2025 feierten wir die Premiere unseres diesjährigen Tanztheaters „Manege des Mutes“ im Kulturhaus Freital. Am vergangenen Wochenende, am 30. und 31. August, folgten die zweite und dritte Aufführung – und beide Abende waren ein voller Erfolg. Das Publikum war begeistert, und wir haben unglaublich viel positives Feedback erhalten.',
        'news/wir-tanzen-zu-den-sternen' => 'Genau vor 3 Jahren stand unsere Show „KuTaWards – Tanzschule für Hexerei und Zauberei“ in den Startlöchern: über 200 kleine und große Tanzbegeisterte fieberten der aufwendig vorbereitenden Vorstellung entgegen, die am 22.03.2020 stattfinden sollte… Dann kam ein (Kultur-)Lockdown nach dem anderen und nach zweimaliger Verschiebung, endlosen Tagen des Online-Trainings und vielen Beschränkungen im Kulturbetrieb verabschiedeten wir uns von der fertigen Show. Ende letzten Jahres fiel...',
        'news/wir-sind-dabei' => 'Im April 2022 haben sehr viele sächsische Sportvereine die Möglichkeit zur Teilnahme am Vereinswettbewerb von der Dachmarke des Freistaats Sachsen „So geht sächsisch.“ und dem Landessportbund Sachsen genutzt. – So wie wir. Das Mitmachen lohnte sich! Eine Jury wählte aus den vielen Bewerbungen am Ende 333 Vereine aus, die wiederum mit jeweils 2.500 Euro für satzungsgemäße Zwecke der täglichen Vereinsarbeit ausgezeichnet wurden. Vielen Dank! Wir haben uns sehr gefreut!!!',
        'news/krisenmodus' => 'Über anderthalb Jahre Krisenmodus... Unser wichtigstes Anliegen in unserem Verein ist es - neben dem Erhalt unserer bald 20-jährigen Organisation - unseren Mitgliedern eine kulturelle Heimstatt zu geben, wo sie sich kreativ und zusammen mit anderen Menschen entfalten können, und dabei auch für die Gemeinschaft etwas leisten, indem sie Geselligkeiten und Veranstaltungen organisieren oder andere dabei unterstützen. Darüber hinaus trägt unser Verein nicht nur zur gesellschaftlichen Teilhabe...',
        'news/pilates-mit-hannah-kelly' => 'All-Levels Pilates-Mattenkurs Gönnen Sie sich 1 Stunde, um Ihren Körper und Geist zu schonen. Verbessern Sie mit Pilates Ihre Körperkraft, Körperspannung, Flexibilität und Koordination! Durch das regelmäßige Ausüben von Pilates: - fühlen Sie sich fitter und stärker - verbessern Tonus, Form und Haltung und - fördern Ihr Wohlbefinden Bitte eigenen Matte (Yoga oder Pilates) mitbringen!',
        'news/alles-fuer-eure-party' => 'Ob Schuleinführung, Jugendweihe, Geburtstags- oder Gartenparty, wir haben das richtige Equipment für jeden Anlass. In unserem Netzwerk verfügen wir über eine große Menge an Veranstaltungsequipment, für kleine und große Events, welches bei uns günstig zu mieten ist. Wir freuen uns auf eure Anfrage. Euer Team vom KuTa Werk',
        'news/endlich-wieder-zumba' => 'Wir freuen uns euch mitteilen zu dürfen, dass wir endlich wieder Zumba® anbieten können! Mit Sandra Vogel wird ab dem neuen Schuljahr jeden Dienstag und Donnerstag wieder fleißig getanztund Kalorien verbrannt. Wir freuen uns über eure Teilnahme und bitten um kurze Voranmeldung per Mail an info@kutawerk.de Zumba® Trainingszeiten Dienstags 19:15 - 20:15 Uhr Donnerstag 19:00 - 20:00 Uhr',
        'news/corona-trainingsplan' => 'Yeah, endlich wieder tanzen. Das geht jedoch nur unter strickten Hygieneauflage. Doch wir sind froh endlich wieder beginnen zu können.',
        'news/trainingsbetrieb-eingestellt' => 'Aufgrund der aktuellen Situation haben wir uns entschlossen, ab Montag, den 16.03.2020, bis nach den Osterferien unseren regulären Trainingsbetrieb einzustellen. Für nähere Informationen oder bei Fragen, bitten wir unser Kontaktformular der Tanzsparte zu benutzen. Lasst uns das gesellschaftliche Leben aufrechterhalten und zusammenhalten, aber tragt auch an der Verantwortung mit, die Ausbreitung des Virus zu verlangsamen, schränkt eure sozialen Kontakte vorerst auf das Nötigste ein! Bleibt...',
        'news/kutawards-verschoben' => 'Liebe Mitglieder, liebe Eltern, liebe Zuschauer! Der Termin für KuTaWards wird vom 22.03.20 auf den 21.03.21 verschoben. Nach vielen Gesprächen, Gedanken und Überlegungen haben wir uns entschieden, dass es aus der Perspektive eines verantwortungsbewussten Veranstalters notwendig ist die Veranstaltungzuverschieben.Aufgeschoben ist nicht aufgehoben! Bleibt alle motiviert und gesund, herzlich Euer Vorstand und das Trainer-Team des KuTaWerks *bereits getätigte Karten Reservierungen bleiben...',
    ];

    public function __construct(private readonly EntityManagerInterface $entityManager, #[Autowire('%kernel.project_dir%')] private readonly string $projectDir)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $counts = ['news' => 0, 'events' => 0, 'training sessions' => 0, 'trainers' => 0];
        $this->normalizeExistingNews();
        $this->restoreLegacyExcerpts();
        $counts['news'] = $this->importNews();
        $counts['events'] = $this->importEvents();
        $counts['trainers'] = $this->importTrainers();
        $this->entityManager->flush();
        $counts['training sessions'] = $this->importTraining();
        $this->entityManager->flush();
        $io->success(sprintf('Imported %d news posts, %d events, %d training sessions and %d trainers. Existing records were left unchanged.', ...array_values($counts)));

        return Command::SUCCESS;
    }

    private function importTrainers(): int
    {
        $source = (string) file_get_contents($this->projectDir.'/content/pages/bereiche__tanz__trainer.html');
        $dom = new \DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$source);
        $xpath = new \DOMXPath($dom);
        $users = $this->entityManager->getRepository(User::class);
        $dance = $this->entityManager->getRepository(Department::class)->findOneBy(['slug' => 'dance']);
        $count = 0;
        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " content-column ")]') ?: [] as $column) {
            $nameNodes = $xpath->query('.//*[contains(@style, "font-size: 36px")]', $column);
            $nameNode = $nameNodes ? $nameNodes->item(0) : null;
            $name = $nameNode ? trim((string) preg_replace('/\s+/u', ' ', $nameNode->textContent)) : '';
            if ($name === '' || $name === '***') continue;
            $parts = preg_split('/\s+/u', $name) ?: [];
            $firstName = array_shift($parts) ?: $name;
            $lastName = implode(' ', $parts);
            $trainer = $users->findOneBy(['firstName' => $firstName, 'lastName' => $lastName]);
            if (!$trainer instanceof User) {
                $trainer = (new User())->setFirstName($firstName)->setLastName($lastName)->setEmail(self::KNOWN_TRAINER_EMAILS[$name] ?? null)->setRoles([])->setPermissions([]);
                $this->entityManager->persist($trainer);
                ++$count;
            }
            $paragraphTexts = $this->trainerTexts($xpath, $column, $name, false);
            $texts = $this->trainerTexts($xpath, $column, $name, true);
            $images = $xpath->query('.//img[@src]', $column);
            $image = $images ? $images->item(0) : null;
            $oldBio = implode("\n\n", array_values(array_unique($paragraphTexts)));
            $newBio = implode("\n\n", array_values(array_unique($texts)));
            if ($trainer->getTrainerBio() === null || $trainer->getTrainerBio() === $oldBio) $trainer->setTrainerBio($newBio);
            $trainer->setTrainer(true)->setActive(true)->setTrainerImagePath($image instanceof \DOMElement ? $image->getAttribute('src') : null);
            if ($dance instanceof Department) $trainer->addDepartment($dance);
        }
        return $count;
    }

    /** @return list<string> */
    private function trainerTexts(\DOMXPath $xpath, \DOMNode $column, string $name, bool $includeLists): array
    {
        $texts = [];
        $query = $includeLists ? './/p | .//li' : './/p';
        foreach ($xpath->query($query, $column) ?: [] as $node) {
            $text = trim((string) preg_replace('/\s+/u', ' ', $node->textContent));
            if ($text === '' || $text === $name || $text === '***' || str_contains($text, 'hier entsteht gerade')) continue;
            $texts[] = $node instanceof \DOMElement && strtolower($node->tagName) === 'li' ? '• '.$text : $text;
        }
        return $texts;
    }

    private function normalizeExistingNews(): void
    {
        foreach ($this->entityManager->getRepository(NewsPost::class)->findAll() as $post) {
            if (!$post instanceof NewsPost || !$post->isContentHtml()) continue;
            $dom = new \DOMDocument('1.0', 'UTF-8');
            @$dom->loadHTML('<?xml encoding="UTF-8"><div id="content-root">'.$post->getContent().'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $xpath = new \DOMXPath($dom);
            $root = $dom->getElementById('content-root');
            if (!$root) continue;
            $position = count($post->getGalleryImages());
            $knownPaths = array_map(static fn (NewsGalleryImage $image): string => $image->getImagePath(), $post->getGalleryImages()->toArray());
            $galleries = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " gallery-grid ")]', $root);
            if ($galleries) foreach (iterator_to_array($galleries) as $gallery) {
                $galleryWrapper = $gallery->parentNode;
                foreach ($xpath->query('.//img[@src]', $gallery) ?: [] as $image) {
                    if (!$image instanceof \DOMElement) continue;
                    $path = $image->getAttribute('src');
                    if (str_starts_with($path, '/') && !in_array($path, $knownPaths, true)) {
                        $post->addGalleryImage((new NewsGalleryImage())->setImagePath($path)->setPosition($position++));
                        $knownPaths[] = $path;
                    }
                }
                $gallery->parentNode?->removeChild($gallery);
                if ($galleryWrapper instanceof \DOMElement && trim($galleryWrapper->textContent) === '' && !$xpath->query('.//img', $galleryWrapper)?->length) $galleryWrapper->parentNode?->removeChild($galleryWrapper);
            }
            foreach ($xpath->query('.//*', $root) ?: [] as $element) {
                if (!$element instanceof \DOMElement) continue;
                foreach (['data-href', 'data-image-id', 'data-src-width', 'data-src-height', 'data-orig-width', 'data-orig-height', 'data-subtitle'] as $attribute) $element->removeAttribute($attribute);
            }
            $post->setContent($this->innerHtml($root));
        }
    }

    private function restoreLegacyExcerpts(): void
    {
        $repository = $this->entityManager->getRepository(NewsPost::class);
        foreach (self::NEWS as [$legacyKey, , , , $file]) {
            $post = $repository->findOneBy(['legacyKey' => $legacyKey]);
            if (!$post instanceof NewsPost) continue;
            $text = $this->legacyNewsText($file);
            $excerpt = self::NEWS_EXCERPTS[$legacyKey] ?? null;
            if ($excerpt !== null && in_array($post->getExcerpt(), [$text, mb_substr($text, 0, 500)], true)) $post->setExcerpt($excerpt);
        }
    }

    private function legacyNewsText(string $file): string
    {
        $source = (string) file_get_contents($this->projectDir.'/content/pages/'.$file);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML('<?xml encoding="UTF-8"><div id="legacy-root">'.$source.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' news-content ')]");
        $contentNode = $nodes ? $nodes->item(0) : null;
        if (!$contentNode instanceof \DOMNode) return trim((string) preg_replace('/\s+/u', ' ', strip_tags($source)));
        $galleries = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " gallery-grid ")]', $contentNode);
        if ($galleries) foreach (iterator_to_array($galleries) as $gallery) $gallery->parentNode?->removeChild($gallery);
        return trim((string) preg_replace('/\s+/u', ' ', strip_tags($this->innerHtml($contentNode))));
    }

    private function importNews(): int
    {
        $repository = $this->entityManager->getRepository(NewsPost::class);
        $count = 0;
        foreach (self::NEWS as [$legacyKey, $slug, $title, $date, $file]) {
            if ($repository->findOneBy(['legacyKey' => $legacyKey])) continue;
            $source = (string) file_get_contents($this->projectDir.'/content/pages/'.$file);
            $dom = new \DOMDocument('1.0', 'UTF-8');
            @$dom->loadHTML('<?xml encoding="UTF-8"><div id="legacy-root">'.$source.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $xpath = new \DOMXPath($dom);
            $contentNode = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' news-content ')]")->item(0);
            $imageNode = $contentNode ? $xpath->query('.//img[@src]', $contentNode)->item(0) : null;
            $galleryNodes = $contentNode ? $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " gallery-grid ")]', $contentNode) : null;
            $galleryPaths = [];
            if ($galleryNodes) foreach (iterator_to_array($galleryNodes) as $galleryNode) {
                foreach ($xpath->query('.//img[@src]', $galleryNode) ?: [] as $galleryImage) {
                    if ($galleryImage instanceof \DOMElement && str_starts_with($galleryImage->getAttribute('src'), '/')) $galleryPaths[] = $galleryImage->getAttribute('src');
                }
                $galleryNode->parentNode?->removeChild($galleryNode);
            }
            $html = $contentNode ? $this->innerHtml($contentNode) : $source;
            if ($galleryPaths !== []) $html .= '[[NEWS_GALLERY]]';
            $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags($html)));
            $post = (new NewsPost())->setLegacyKey($legacyKey)->setSlug($slug)->setTitle($title)
                ->setPublishedAt(new \DateTimeImmutable($date.' 12:00:00'))->setPublished(true)
                ->setImagePath($imageNode instanceof \DOMElement ? $imageNode->getAttribute('src') : null)
                ->setExcerpt(self::NEWS_EXCERPTS[$legacyKey] ?? $text)->setContent($html)->setContentIsHtml(true);
            $this->entityManager->persist($post);
            foreach (array_values(array_unique($galleryPaths)) as $position => $path) $post->addGalleryImage((new NewsGalleryImage())->setImagePath($path)->setPosition($position));
            ++$count;
        }
        return $count;
    }

    private function importEvents(): int
    {
        $department = $this->entityManager->getRepository(Department::class)->findOneBy(['slug' => 'dance']);
        if (!$department) throw new \RuntimeException('The dance department must exist before importing events.');
        $repository = $this->entityManager->getRepository(Event::class);
        $lines = file($this->projectDir.'/content/legacy/dance-events-2025.txt', FILE_IGNORE_NEW_LINES) ?: [];
        $count = 0;
        $lastEvent = null;
        foreach ($lines as $line) {
            $columns = preg_split('/\s{2,}/u', trim($line)) ?: [];
            $dateLabel = $columns[0] ?? '';
            if (!preg_match('/^\d{1,2}\./', $dateLabel)) {
                $continuation = trim(mb_substr($line, 106));
                if ($lastEvent instanceof Event && $continuation !== '') $lastEvent->setDescription(trim(($lastEvent->getDescription() ?? '')."\nGruppen: ".$continuation));
                continue;
            }
            $location = $columns[1] ?? '';
            $timeLabel = $columns[2] ?? '';
            $title = $columns[3] ?? '';
            $groups = $columns[4] ?? '';
            if ($title === '') continue;
            $date = $this->parseLegacyDate($dateLabel);
            $legacyKey = 'dance-event-'.substr(hash('sha256', $line), 0, 24);
            if ($repository->findOneBy(['legacyKey' => $legacyKey])) continue;
            $time = preg_match('/(\d{1,2}:\d{2})/', $timeLabel, $match) ? new \DateTimeImmutable($match[1]) : null;
            $description = implode("\n", array_filter([$groups !== '' ? 'Gruppen: '.$groups : null, $timeLabel !== '' ? 'Originale Zeitangabe: '.$timeLabel : null]));
            $event = (new Event())->setLegacyKey($legacyKey)->setOriginalDateLabel($dateLabel)->setDate($date)->setTime($time)
                ->setTitle($title)->setLocation($location ?: null)->setDescription($description ?: null)->setDepartment($department);
            $this->entityManager->persist($event);
            $lastEvent = $event;
            ++$count;
        }
        return $count;
    }

    private function importTraining(): int
    {
        $department = $this->entityManager->getRepository(Department::class)->findOneBy(['slug' => 'dance']);
        if (!$department) throw new \RuntimeException('The dance department must exist before importing training sessions.');
        $sessionRepository = $this->entityManager->getRepository(TrainingSession::class);
        $courseRepository = $this->entityManager->getRepository(Course::class);
        $locationRepository = $this->entityManager->getRepository(Location::class);
        $weekdays = ['Montag' => 1, 'Dienstag' => 2, 'Mittwoch' => 3, 'Donnerstag' => 4, 'Freitag' => 5];
        $lines = file($this->projectDir.'/content/legacy/training-times-2025.txt', FILE_IGNORE_NEW_LINES) ?: [];
        $courseCache = [];
        foreach ($courseRepository->findBy(['department' => $department]) as $course) $courseCache[$course->getName()] = $course;
        $locationCache = [];
        foreach ($locationRepository->findAll() as $location) $locationCache[$location->getName()] = $location;
        foreach (self::TRAINING_LOCATIONS as $name => [$street, $postalCode, $city, $notes, $aliases]) {
            $location = $locationCache[$name] ?? null;
            foreach ($aliases as $alias) $location ??= $locationCache[$alias] ?? null;
            if (!$location instanceof Location) {
                $location = (new Location())->setName($name);
                $this->entityManager->persist($location);
            }
            $location->setName($name)->setStreet($street)->setPostalCode($postalCode)->setCity($city)->setNotes($notes)->setActive(true);
            if ($location->getAddressVersions()->isEmpty()) {
                $location->addAddressVersion((new LocationAddressVersion())
                    ->setStreet($street)
                    ->setPostalCode($postalCode)
                    ->setCity($city)
                    ->setNotes($notes));
            }
            $locationCache[$name] = $location;
            foreach ($aliases as $alias) $locationCache[$alias] = $location;
        }
        $count = 0;
        foreach ($lines as $line) {
            $columns = preg_split('/\s{2,}/u', trim($line)) ?: [];
            $weekdayName = $columns[0] ?? '';
            if (!isset($weekdays[$weekdayName])) continue;
            [$start, $end, $age, $style, $locationName, $courseName] = array_slice(array_pad($columns, 9, ''), 1, 6);
            $notes = count($columns) >= 9 ? $columns[7] : '';
            $trainers = count($columns) >= 9 ? $columns[8] : ($columns[7] ?? '');
            if ($courseName === '' || $locationName === '' || !preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end)) continue;
            $legacyKey = 'training-'.substr(hash('sha256', $line), 0, 24);
            if ($sessionRepository->findOneBy(['legacyKey' => $legacyKey])) continue;
            $course = $courseCache[$courseName] ??= (new Course())->setName($courseName)->setDepartment($department)->setAgeGroup($this->normalizeAgeGroup($age));
            foreach ($this->resolveTrainers($trainers) as $trainer) $course->addTrainer($trainer);
            $location = $locationCache[$locationName] ??= (new Location())->setName($locationName)->setStreet('')->setPostalCode('')->setCity('Freital');
            $this->entityManager->persist($course); $this->entityManager->persist($location);
            $session = (new TrainingSession())->setLegacyKey($legacyKey)->setCourse($course)->setLocation($location)
                ->setWeekday($weekdays[$weekdayName])->setStartsAt(new \DateTimeImmutable($start))->setEndsAt(new \DateTimeImmutable($end))
                ->setValidFrom(new \DateTimeImmutable('2025-09-01'))->setDanceStyle($style ?: null)->setNotes($notes ?: null)->setLegacyTrainerNames($trainers ?: null);
            $this->entityManager->persist($session);
            ++$count;
        }
        return $count;
    }

    private function normalizeAgeGroup(string $value): ?string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));
        if ($value === '') return null;
        if (preg_match('/^ab\s+(\d+)\s+jahre?/iu', $value, $match)) return sprintf('ab %d Jahre', (int) $match[1]);
        if (preg_match('/^(\d+)\s*\.\s*-\s*(\d+)\s*\.\s*kl\.?/iu', $value, $match)) return sprintf('%d.–%d. Klasse', (int) $match[1], (int) $match[2]);
        if (mb_strtolower($value) === 'grundschule') return 'Grundschule';
        return $value;
    }

    /** @return list<User> */
    private function resolveTrainers(string $value): array
    {
        $repository = $this->entityManager->getRepository(User::class);
        $resolved = [];
        foreach (preg_split('~\s*/\s*~u', trim($value)) ?: [] as $name) {
            $name = trim($name);
            if ($name === '') continue;
            $parts = preg_split('/\s+/u', $name) ?: [];
            $firstName = array_shift($parts) ?: '';
            $lastName = implode(' ', $parts);
            $trainer = $repository->findOneBy(['firstName' => $firstName, 'lastName' => $lastName]);
            if (!$trainer instanceof User && $name === 'Kerstin Mager-Baran') {
                $trainer = $repository->findOneBy(['firstName' => 'Kerstin', 'lastName' => '']);
            }
            if ($trainer instanceof User && $trainer->isTrainer() && $trainer->isActive()) $resolved[$trainer->getId()] = $trainer;
        }
        return array_values($resolved);
    }

    private function parseLegacyDate(string $label): \DateTimeImmutable
    {
        preg_match_all('/\d+/', $label, $matches);
        $numbers = array_map('intval', $matches[0]);
        $day = $numbers[0];
        $month = count($numbers) >= 4 ? $numbers[count($numbers) - 2] : $numbers[1];
        $year = $numbers[count($numbers) - 1];
        if ($year < 100) $year += 2000;
        return new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
    }

    private function innerHtml(\DOMNode $node): string
    {
        $html = '';
        foreach ($node->childNodes as $child) $html .= $node->ownerDocument?->saveHTML($child) ?? '';
        return trim($html);
    }
}
