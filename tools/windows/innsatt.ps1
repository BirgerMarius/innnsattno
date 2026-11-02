$ErrorActionPreference = "Stop"

function Show-InnsattMenu {
    Clear-Host
    Write-Host "=====================================" -ForegroundColor Cyan
    Write-Host "          INNSATT.NO VERKTØY"
    Write-Host "=====================================" -ForegroundColor Cyan
    Write-Host
    Write-Host "1  Åpne prosjektet i VS Code"
    Write-Host "2  SSH til docker01"
    Write-Host "3  Deploy innsatt.no til produksjon" -ForegroundColor Yellow
    Write-Host "4  Åpne lokal testside"
    Write-Host "5  Åpne innsatt.no"
    Write-Host "6  Åpne visitasjonsverktøy lokalt"
    Write-Host "7  Åpne visitasjonsverktøy i produksjon"
    Write-Host "8  SSH til produksjonsserver"
    Write-Host "9  Oppdater cache/data"
    Write-Host "0  Avslutt"
    Write-Host
}

function Show-InnsattCacheMenu {
    while ($true) {
        Clear-Host
        Write-Host "=====================================" -ForegroundColor Cyan
        Write-Host "       OPPDATER CACHE/DATA"
        Write-Host "=====================================" -ForegroundColor Cyan
        Write-Host
        Write-Host "1  Oppdater alt"
        Write-Host "2  Oppdater Ringerike"
        Write-Host "3  Oppdater Ilseng"
        Write-Host "4  Oppdater Kongsvinger"
        Write-Host "0  Tilbake"
        Write-Host
        Write-Host "Fengselsvalg oppdaterer:"
        Write-Host "Dagens TV-guide, lokale nyheter, vær og eventuelle bønnetider."
        Write-Host
        Write-Host "Oppdater alt inkluderer også:"
        Write-Host "Felles fotball-/VM-data, navnedager, Dagen i dag og nyhetsfeeder."
        Write-Host
        Write-Host "Fungerende reservedata beholdes ved kildefeil."
        Write-Host

        $CacheChoice = Read-Host "Velg"

        if ($CacheChoice -eq "0") {
            return
        }

        $RefreshArgument = switch ($CacheChoice) {
            "1" { "--all" }
            "2" { "--prison=ringerike" }
            "3" { "--prison=ilseng" }
            "4" { "--prison=kongsvinger" }
            default { $null }
        }

        if (-not $RefreshArgument) {
            Write-Host "Ugyldig valg." -ForegroundColor Yellow
            Read-Host "Trykk Enter for å fortsette"
            continue
        }

        try {
            Write-Host
            Write-Host "Starter oppdatering i produksjon..." -ForegroundColor Green
            Write-Host "Dette kan ta litt tid."
            Write-Host

            $RemoteCommand = "cd /home/forge/innsatt.no/innnsattno && sudo -u forge /usr/bin/php artisan external-data:refresh $RefreshArgument"

            ssh innsatt-prod $RemoteCommand
            $RefreshExitCode = $LASTEXITCODE

            Write-Host

            if ($RefreshExitCode -eq 0) {
                Write-Host "Oppdateringen er fullført." -ForegroundColor Green
            } else {
                Write-Host "Oppdateringen meldte feil eller delvis feil." -ForegroundColor Yellow
                Write-Host "Se resultatet over. Feilkode: $RefreshExitCode" -ForegroundColor Yellow
            }
        } catch {
            Write-Host
            Write-Host "Kunne ikke fullføre oppdateringen: $_" -ForegroundColor Red
        }

        Read-Host "Trykk Enter for å gå tilbake til cachemenyen"
    }
}

while ($true) {
    Show-InnsattMenu
    $Choice = Read-Host "Velg"

    switch ($Choice) {
        "1" {
            Write-Host "Åpner prosjektet via Remote-SSH..." -ForegroundColor Green
            code --remote ssh-remote+docker01 /home/aiworker/workspace/projects/innsatt-test
            break
        }

        "2" {
            Write-Host "Kobler til docker01..." -ForegroundColor Green
            ssh docker01
        }

        "3" {
            Write-Host
            Write-Host "Dette oppdaterer innsatt.no i produksjon." -ForegroundColor Yellow
            $Confirm = Read-Host "Kjøre produksjonsdeploy? [j/N]"

            if ($Confirm -eq "j") {
                Write-Host "Starter produksjonsdeploy..." -ForegroundColor Green
                ssh -t innsatt-prod "innsatt-deploy"
                Read-Host "Trykk Enter for å gå tilbake til menyen"
            }
        }

        "4" {
            Start-Process "http://192.168.1.100:8086"
            break
        }

        "5" {
            Start-Process "https://innsatt.no"
            break
        }

        "6" {
            Start-Process "http://192.168.1.100:8086/visitasjon"
            break
        }

        "7" {
            Start-Process "https://innsatt.no/visitasjon"
            break
        }

        "8" {
            Write-Host "Kobler til produksjonsserveren..." -ForegroundColor Green
            ssh innsatt-prod
        }

        "9" {
            Show-InnsattCacheMenu
        }

        "0" {
            return
        }

        default {
            Write-Host "Ugyldig valg." -ForegroundColor Yellow
            Start-Sleep -Seconds 1
        }
    }
}