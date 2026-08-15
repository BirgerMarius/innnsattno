<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Symfony\Component\Process\Process;
use Throwable;

class ChangeHistoryService
{
    private const CACHE_KEY = 'public-change-history.v2';

    private const MESSAGE_TRANSLATIONS = [
        'Final changes' => 'Siste endringer',
        'Add Ilseng TV guide page' => 'Legg til TV-guide for Ilseng',
        'Add Ilseng print route' => 'Legg til utskriftsrute for Ilseng',
        'Add Ilseng TV guide and print page' => 'Legg til TV-guide og utskriftsside for Ilseng',
        'Add flag day information' => 'Legg til informasjon om flaggdager',
        'Fix flag day date format' => 'Rett datoformat for flaggdager',
        'Fix flag day calculation' => 'Rett beregning av flaggdager',
        'Update Ilseng channel list' => 'Oppdater kanallisten for Ilseng',
        'Fix print-ilseng route' => 'Rett utskriftsruten for Ilseng',
        'Restore print-ilseng' => 'Gjenopprett utskrift for Ilseng',
        'Add remaining Ilseng channels' => 'Legg til resterende Ilseng-kanaler',
        'Add print icon to Ilseng button' => 'Legg til utskriftsikon på Ilseng-knappen',
        'Add Bonnetid API token' => 'Legg til API-token for Bonnetid',
        'Create prayer times page' => 'Opprett side for bønnetider',
        'Add prayer times button' => 'Legg til knapp for bønnetider',
        'Add monthly prayer times' => 'Legg til månedlige bønnetider',
        'Add movable flag days' => 'Legg til bevegelige flaggdager',
        'Fix FootballController syntax' => 'Rett syntaks i FootballController',
        'Add flags to football tables' => 'Legg til flagg i fotballtabeller',
        'Fix syntax error in football controller' => 'Rett syntaksfeil i fotballkontrolleren',
        'Fix missing World Cup flags' => 'Rett manglende VM-flagg',
        'Improve front page button layout and color coding' => 'Forbedre knappeoppsett og fargekoding på forsiden',
        'Improve front page layout with responsive button grid' => 'Forbedre forsiden med responsivt knapperutenett',
        'Improve spin wheel UX and add front page shortcut' => 'Forbedre brukeropplevelsen for lykkehjulet og legg til snarvei på forsiden',
        'Feature: add feedback system with email notifications' => 'Legg til tilbakemeldingssystem med e-postvarsler',
        'Add admin interface for feedback management' => 'Legg til administrasjon for tilbakemeldinger',
        'Add Premier League foundation and public landing page' => 'Legg til grunnlag for Premier League og offentlig startside',
        'Improve global navigation with clickable logo and shared prayer page header' => 'Forbedre navigasjonen med klikkbar logo og felles topptekst for bønnetider',
        'Add printable monthly calendar with Norwegian holidays' => 'Legg til utskrivbar månedskalender med norske helligdager',
        'Add recommended professional resources portal with admin management' => 'Legg til portal for anbefalte fagressurser med administrasjon',
        'Add Schibsted football API explorer and Premier League integration' => 'Legg til API-utforsker for Schibsted fotball og Premier League-integrasjon',
        'Add Eliteserien integration using Schibsted SportsNext API' => 'Legg til Eliteserien-integrasjon via Schibsted SportsNext API',
        'Improve spin wheel page design and winner effects' => 'Forbedre utforming og vinnereffekter for lykkehjulet',
        'Add curated corrections news portal' => 'Legg til kuratert nyhetsportal for kriminalomsorgen',
        'Add news navigation links' => 'Legg til navigasjonslenker for nyheter',
        'Replace World Cup front page link with visitation roulette' => 'Erstatt VM-lenken på forsiden med visitasjonsverktøyet',
        'Fix visitation assets in production' => 'Rett ressurser for visitasjonsverktøyet i produksjon',
        'Fix visitation icon sizing and CSS cache' => 'Rett ikonstørrelser for visitasjonsverktøyet og CSS-mellomlager',
        'Replace front page print notice with officer tribute' => 'Erstatt utskriftsmeldingen på forsiden med markering for fengselsbetjenter',
        'Use Work Package-specific quality commands' => 'Bruk kvalitetssjekker tilpasset arbeidspakker',
        'Add weather forecast for Ringerike prison' => 'Legg til værvarsel for Ringerike fengsel',
        'Add local Docker development environment' => 'Legg til lokalt utviklingsmiljø med Docker',
        'Update project analysis and work package status' => 'Oppdater prosjektanalyse og status for arbeidspakker',
        'Reorganize homepage and add Ilseng weather forecast' => 'Omorganiser forsiden og legg til værvarsel for Ilseng',
        'Add Ringerike prison news column' => 'Legg til nyhetskolonne for Ringerike fengsel',
        'Fix responsive homepage news layout' => 'Rett responsivt nyhetsoppsett på forsiden',
        'Improve flag day information and homepage display' => 'Forbedre informasjon om flaggdager og visningen på forsiden',
        'Add automated today information page' => 'Legg til automatisk side for Dagen i dag',
        'Add centralized admin dashboard' => 'Legg til sentralisert administrasjonsside',
        'Add public change history' => 'Legg til offentlig endringslogg',
        'Improve automated today content' => 'Forbedre automatisk innhold for Dagen i dag',
        'Add integrated admin portal statistics' => 'Legg til integrert statistikk i administrasjonsportalen',
        'Add top page admin statistics' => 'Legg til statistikk øverst på administrasjonssiden',
        'Fix rolling football print windows' => 'Rett tidsperioder for fotballutskrift',
        'Fix print route' => 'Rett utskriftsrute',
        'Fix print route and channel list' => 'Rett utskriftsrute og kanalliste',
    ];

    private CacheRepository $cache;

    public function __construct(CacheRepository $cache)
    {
        $this->cache = $cache;
    }

    public function get(): array
    {
        return $this->cache->remember(
            self::CACHE_KEY,
            max(1, (int) config('change-history.cache_ttl', 3600)),
            fn () => $this->readHistory()
        );
    }

    private function readHistory(): array
    {
        $repositoryPath = $this->repositoryPath();

        if ($repositoryPath === null) {
            return $this->unavailableResult();
        }

        try {
            $process = new Process([
                'git', '-C', $repositoryPath, 'log', '--no-merges',
                '--format=%H%x1f%cI%x1f%s%x1e',
            ]);
            $process->setTimeout(10);
            $process->run();

            if (!$process->isSuccessful()) {
                return $this->unavailableResult();
            }

            $changes = $this->parse($process->getOutput());

            if ($changes === []) {
                return $this->unavailableResult();
            }

            return [
                'available' => true,
                'updated_at' => $changes[0]['date'],
                'groups' => collect($changes)
                    ->groupBy(fn (array $change) => $change['date']->format('Y-m-d'))
                    ->map(fn ($items) => $items->values()->all())
                    ->all(),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return $this->unavailableResult();
        }
    }

    private function repositoryPath(): ?string
    {
        $configuredPath = config('change-history.repository_path');
        $path = $configuredPath ?: base_path();

        if (!is_string($path) || !$this->isAbsolutePath($path)) {
            return null;
        }

        $realPath = realpath($path);

        return $realPath !== false && is_dir($realPath.DIRECTORY_SEPARATOR.'.git')
            ? $realPath
            : null;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    private function parse(string $output): array
    {
        $changes = [];

        foreach (explode("\x1e", $output) as $record) {
            $fields = explode("\x1f", trim($record));

            if (count($fields) !== 3 || trim($fields[2]) === '') {
                continue;
            }

            try {
                $date = CarbonImmutable::parse($fields[1])->locale('nb');
            } catch (Throwable $exception) {
                continue;
            }

            $message = trim($fields[2]);

            $changes[] = [
                'date' => $date,
                'message' => self::MESSAGE_TRANSLATIONS[$message] ?? $message,
            ];
        }

        return $changes;
    }

    private function unavailableResult(): array
    {
        return ['available' => false, 'updated_at' => null, 'groups' => []];
    }
}
