<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

final class LegacyRedirectController extends AbstractController
{
    #[Route('/über-uns', name: 'legacy_about', methods: ['GET'], defaults: ['target' => 'about'])]
    #[Route('/über-uns/downloads', name: 'legacy_about_downloads', methods: ['GET'], defaults: ['target' => 'about_downloads'])]
    #[Route('/bereiche', name: 'legacy_areas', methods: ['GET'], defaults: ['target' => 'area_index'])]
    #[Route('/bereiche/kultur', name: 'legacy_area_culture', methods: ['GET'], defaults: ['target' => 'area_culture'])]
    #[Route('/bereiche/technik', name: 'legacy_area_technology', methods: ['GET'], defaults: ['target' => 'area_technology'])]
    #[Route('/bereiche/tanz', name: 'legacy_dance', methods: ['GET'], defaults: ['target' => 'dance_index'])]
    #[Route('/bereiche/tanz/trainer', name: 'legacy_dance_trainers', methods: ['GET'], defaults: ['target' => 'dance_trainers'])]
    #[Route('/bereiche/tanz/trainingszeiten', name: 'legacy_dance_schedule', methods: ['GET'], defaults: ['target' => 'dance_schedule'])]
    #[Route('/bereiche/tanz/termine', name: 'legacy_dance_dates', methods: ['GET'], defaults: ['target' => 'dance_dates'])]
    #[Route('/bereiche/tanz/vertrag', name: 'legacy_dance_membership', methods: ['GET'], defaults: ['target' => 'dance_contract'])]
    #[Route('/bereiche/kuta-lounge', name: 'legacy_lounge', methods: ['GET'], defaults: ['target' => 'lounge_index'])]
    #[Route('/bereiche/kuta-lounge/vermietung', name: 'legacy_lounge_rental', methods: ['GET'], defaults: ['target' => 'lounge_rental'])]
    #[Route('/kontakt', name: 'legacy_contact', methods: ['GET'], defaults: ['target' => 'contact_index'])]
    #[Route('/kontakt/vorstand', name: 'legacy_contact_board', methods: ['GET'], defaults: ['target' => 'contact_board'])]
    #[Route('/kontakt/tanzsparte', name: 'legacy_contact_dance', methods: ['GET'], defaults: ['target' => 'contact_dance'])]
    #[Route('/kontakt/tanzsparte/karten-vorverkauf', name: 'legacy_contact_tickets', methods: ['GET'], defaults: ['target' => 'contact_tickets'])]
    #[Route('/kontakt/kultursparte', name: 'legacy_contact_culture', methods: ['GET'], defaults: ['target' => 'contact_culture'])]
    #[Route('/kontakt/techniksparte', name: 'legacy_contact_technology', methods: ['GET'], defaults: ['target' => 'contact_technology'])]
    #[Route('/kontakt/kuta-lounge', name: 'legacy_contact_lounge', methods: ['GET'], defaults: ['target' => 'contact_lounge'])]
    #[Route('/impressum', name: 'legacy_imprint', methods: ['GET'], defaults: ['target' => 'legal_imprint'])]
    #[Route('/about', name: 'legacy_provider_imprint', methods: ['GET'], defaults: ['target' => 'legal_imprint'])]
    #[Route('/datenschutz', name: 'legacy_privacy', methods: ['GET'], defaults: ['target' => 'legal_privacy'])]
    #[Route('/j/privacy', name: 'legacy_provider_privacy', methods: ['GET'], defaults: ['target' => 'legal_privacy'])]
    #[Route('/vertrag-widerrufen', name: 'legacy_withdrawal', methods: ['GET'], defaults: ['target' => 'legal_withdrawal'])]
    #[Route('/j/withdrawal', name: 'legacy_provider_withdrawal', methods: ['GET'], defaults: ['target' => 'legal_withdrawal'])]
    #[Route('/veranstaltungen', name: 'legacy_events', methods: ['GET'], defaults: ['target' => 'events'])]
    #[Route('/redaktion', name: 'legacy_editor', methods: ['GET'], defaults: ['target' => 'editor_dashboard'])]
    #[Route('/redaktion/anmelden', name: 'legacy_editor_login', methods: ['GET'], defaults: ['target' => 'app_login'])]
    #[Route('/news/manege-des-mutes-2025', name: 'legacy_news_circus', methods: ['GET'], defaults: ['target' => 'news_article', 'slug' => 'circus-of-courage-2025'])]
    #[Route('/news/wir-tanzen-zu-den-sternen', name: 'legacy_news_stars', methods: ['GET'], defaults: ['target' => 'news_article', 'slug' => 'dancing-to-the-stars'])]
    #[Route('/news/wir-sind-dabei', name: 'legacy_news_winner', methods: ['GET'], defaults: ['target' => 'news_article', 'slug' => 'association-competition-winner'])]
    #[Route('/news/krisenmodus', name: 'legacy_news_crisis', methods: ['GET'], defaults: ['target' => 'news_article', 'slug' => 'crisis-mode'])]
    #[Route('/news/pilates-mit-hannah-kelly', name: 'legacy_news_pilates', methods: ['GET'], defaults: ['target' => 'news_article', 'slug' => 'pilates-with-hannah-kelly'])]
    #[Route('/news/alles-fuer-eure-party', name: 'legacy_news_party', methods: ['GET'], defaults: ['target' => 'news_article', 'slug' => 'everything-for-your-party'])]
    #[Route('/news/endlich-wieder-zumba', name: 'legacy_news_zumba', methods: ['GET'], defaults: ['target' => 'news_article', 'slug' => 'zumba-is-back'])]
    #[Route('/news/corona-trainingsplan', name: 'legacy_news_coronavirus', methods: ['GET'], defaults: ['target' => 'news_article', 'slug' => 'coronavirus-training-schedule'])]
    #[Route('/news/trainingsbetrieb-eingestellt', name: 'legacy_news_suspended', methods: ['GET'], defaults: ['target' => 'news_article', 'slug' => 'dance-training-suspended'])]
    #[Route('/news/kutawards-verschoben', name: 'legacy_news_kutawards', methods: ['GET'], defaults: ['target' => 'news_article', 'slug' => 'kutawards-postponed'])]
    #[Route('/2025/09/04/manege-des-mutes-2025', name: 'legacy_dated_circus', methods: ['GET'], defaults: ['target' => 'news_article', 'slug' => 'circus-of-courage-2025'])]
    #[Route('/2023/04/21/wir-tanzen-zu-den-sternen', name: 'legacy_dated_stars', methods: ['GET'], defaults: ['target' => 'news_article', 'slug' => 'dancing-to-the-stars'])]
    #[Route('/2022/12/07/wir-sind-dabei', name: 'legacy_dated_winner', methods: ['GET'], defaults: ['target' => 'news_article', 'slug' => 'association-competition-winner'])]
    #[Route('/2021/11/10/-', name: 'legacy_dated_crisis', methods: ['GET'], defaults: ['target' => 'news_article', 'slug' => 'crisis-mode'])]
    #[Route('/2020/10/02/neu-pilates-ab-20-10-mit-hannah-kelly', name: 'legacy_dated_pilates', methods: ['GET'], defaults: ['target' => 'news_article', 'slug' => 'pilates-with-hannah-kelly'])]
    #[Route('/2020/08/05/alles-für-eure-party', name: 'legacy_dated_party', methods: ['GET'], defaults: ['target' => 'news_article', 'slug' => 'everything-for-your-party'])]
    #[Route('/2020/07/31/endlich-wieder-zumba', name: 'legacy_dated_zumba', methods: ['GET'], defaults: ['target' => 'news_article', 'slug' => 'zumba-is-back'])]
    #[Route('/2020/05/08/trainingsbetrieb-wieder-aufgenommen-coronatrainingsplan', name: 'legacy_dated_coronavirus', methods: ['GET'], defaults: ['target' => 'news_article', 'slug' => 'coronavirus-training-schedule'])]
    #[Route('/2020/03/15/trainingsbetrieb-der-tanzsparte-eingestellt', name: 'legacy_dated_suspended', methods: ['GET'], defaults: ['target' => 'news_article', 'slug' => 'dance-training-suspended'])]
    #[Route('/2020/03/10/kutawards-veranstaltung-verschoben-auf-21-06-2020', name: 'legacy_dated_kutawards', methods: ['GET'], defaults: ['target' => 'news_article', 'slug' => 'kutawards-postponed'])]
    public function redirectLegacy(string $target, ?string $slug = null): RedirectResponse
    {
        return $this->redirectToRoute($target, $slug === null ? [] : ['slug' => $slug], RedirectResponse::HTTP_MOVED_PERMANENTLY);
    }
}
