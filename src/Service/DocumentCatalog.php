<?php
declare(strict_types=1);

namespace App\Service;

final class DocumentCatalog
{
    public const ITEMS = [
        'association_statutes'=>['title'=>'Vereinssatzung','group'=>'Verein','description'=>'Satzung der Kultur- und Tanzwerkstatt e.V.'],
        'lounge_contract'=>['title'=>'Nutzungsvertrag KuTa Lounge','group'=>'KuTa Lounge','description'=>'Vertrag für die Nutzung der KuTa Lounge'],
        'membership_application'=>['title'=>'Allgemeiner Mitgliedsantrag','group'=>'Mitgliedschaft','description'=>'Aufnahmeantrag der Kultur- und Tanzwerkstatt e.V.'],
        'garde_application'=>['title'=>'Mitgliedsantrag Gardetanz','group'=>'Mitgliedschaft','description'=>'Antrag für die Kooperation mit dem Faschingsverein Hainsberg e.V.'],
        'dance_contributions'=>['title'=>'Beitragsordnung Tanzsparte','group'=>'Mitgliedschaft','description'=>'Beitragsordnung der Tanzsparte'],
    ];
    public function all(): array { return self::ITEMS; }
    public function has(string $key): bool { return isset(self::ITEMS[$key]); }
}
