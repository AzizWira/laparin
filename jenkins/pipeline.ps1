[CmdletBinding()]
param(
    [Parameter(Mandatory)]
    [ValidateSet('Check', 'Test', 'Build', 'Push', 'Deploy', 'Smoke')]
    [string] $Action
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

function Invoke-Docker {
    & docker @args
    if ($LASTEXITCODE -ne 0) {
        throw "Docker gagal (exit $LASTEXITCODE) saat menjalankan: $($args -join ' ')"
    }
}

function Get-LaparinImage {
    if ($env:DOCKERHUB_USER -cnotmatch '^[a-z0-9][a-z0-9_-]*$' -or $env:LAPARIN_TAG -cnotmatch '^[0-9a-f]{12}$') {
        throw 'Username Docker Hub atau tag commit tidak valid.'
    }

    return "$env:DOCKERHUB_USER/laparin"
}

function Get-LaparinDeployment {
    $targetName = $env:LAPARIN_TARGET
    $port = switch -CaseSensitive ($targetName) {
        'staging' { 8101 }
        'production' { 8100 }
        default { throw 'TARGET harus staging atau production.' }
    }

    return @{
        Name = $targetName
        Project = "laparin-$targetName"
        Port = $port
        Url = "http://127.0.0.1:$port"
    }
}

function Invoke-WithDockerHub([scriptblock] $Operation) {
    if ([string]::IsNullOrWhiteSpace($env:DOCKERHUB_PAT)) {
        throw 'PAT Docker Hub belum tersedia di Jenkins Credentials.'
    }

    $endpoint = (Invoke-Docker context inspect --format '{{.Endpoints.docker.Host}}').Trim()
    if ($endpoint -notlike 'npipe:*') {
        throw 'Deploy memerlukan Docker Engine lokal melalui named pipe Windows.'
    }

    $tempParent = [IO.Path]::GetFullPath([IO.Path]::GetTempPath())
    $configPath = Join-Path $tempParent ('laparin-docker-' + [Guid]::NewGuid().ToString('N'))
    $oldConfig = $env:DOCKER_CONFIG
    $oldHost = $env:DOCKER_HOST
    $oldContext = $env:DOCKER_CONTEXT

    New-Item -ItemType Directory -Path $configPath | Out-Null
    try {
        # The registry entry prevents Docker from selecting the shared Windows credential helper.
        '{"auths":{"https://index.docker.io/v1/":{}}}' |
            Set-Content -LiteralPath (Join-Path $configPath 'config.json') -Encoding ASCII
        $env:DOCKER_CONFIG = $configPath
        $env:DOCKER_HOST = $endpoint
        $env:DOCKER_CONTEXT = $null

        $env:DOCKERHUB_PAT | & docker login --username $env:DOCKERHUB_USER --password-stdin
        if ($LASTEXITCODE -ne 0) {
            throw 'Login Docker Hub gagal. Periksa username dan PAT di Jenkins Credentials.'
        }

        & $Operation
    } finally {
        $env:DOCKER_CONFIG = $oldConfig
        $env:DOCKER_HOST = $oldHost
        $env:DOCKER_CONTEXT = $oldContext
        $createdDirectory = Get-Item -LiteralPath $configPath
        if ($createdDirectory.FullName -eq $configPath -and -not $createdDirectory.LinkType) {
            Remove-Item -LiteralPath $createdDirectory.FullName -Recurse -Force
        }
    }
}

switch ($Action) {
    'Check' {
        $target = Get-LaparinDeployment
        Write-Output "Target $($target.Name), project $($target.Project), port $($target.Port)."
        Invoke-Docker version
        Invoke-Docker compose version
    }
    'Test' {
        $testImage = $env:LAPARIN_TEST_IMAGE
        if ($testImage -cnotmatch '^laparin-ci-test:[0-9a-f]{12}-[0-9]+$') {
            throw 'Nama image test tidak valid.'
        }

        Invoke-Docker build --target test --tag $testImage .
        try {
            Invoke-Docker run --rm $testImage
        } finally {
            & docker image rm $testImage
            if ($LASTEXITCODE -ne 0) {
                Write-Warning "Image test sementara belum terhapus: $testImage."
            }
        }
    }
    'Build' {
        $image = Get-LaparinImage
        Invoke-Docker build --target runtime --tag "${image}:$env:LAPARIN_TAG" --tag "${image}:latest" .
    }
    'Push' {
        $image = Get-LaparinImage
        Invoke-WithDockerHub {
            Invoke-Docker push "${image}:$env:LAPARIN_TAG"
            Invoke-Docker push "${image}:latest"
        }
    }
    'Deploy' {
        $target = Get-LaparinDeployment
        if ([string]::IsNullOrWhiteSpace($env:LAPARIN_ENV_FILE) -or -not (Test-Path -LiteralPath $env:LAPARIN_ENV_FILE -PathType Leaf)) {
            throw 'File konfigurasi target belum tersedia di Jenkins Credentials.'
        }

        $env:LAPARIN_IMAGE = Get-LaparinImage
        $env:LAPARIN_BIND_IP = '127.0.0.1'
        $env:LAPARIN_PORT = [string] $target.Port
        $env:LAPARIN_APP_ENV = $target.Name
        $env:LAPARIN_APP_URL = $target.Url
        $env:LAPARIN_DB_NAME = "laparin_$($target.Name)"
        $compose = @('compose', '--env-file', $env:LAPARIN_ENV_FILE, '--project-name', $target.Project, '--file', 'compose.yaml')

        Invoke-Docker @compose config --quiet
        Invoke-WithDockerHub {
            Invoke-Docker @compose pull app
            Invoke-Docker @compose up --detach --no-build --wait --wait-timeout 120
            Invoke-Docker @compose ps
        }
    }
    'Smoke' {
        $target = Get-LaparinDeployment
        $endpoint = "$($target.Url)/health"
        for ($attempt = 1; $attempt -le 12; $attempt++) {
            try {
                $response = Invoke-WebRequest -Uri $endpoint -UseBasicParsing -TimeoutSec 5 -MaximumRedirection 0
                $payload = $response.Content | ConvertFrom-Json
                if ($response.StatusCode -eq 200 -and $payload.status -eq 'ok' -and $payload.database -eq 'connected') {
                    Write-Output "$endpoint mengembalikan HTTP 200, database connected."
                    return
                }
            } catch {
                Write-Output "Smoke test percobaan $attempt belum mendapat respons sehat."
            }
            if ($attempt -lt 12) {
                Start-Sleep -Seconds 3
            }
        }

        throw "Smoke test gagal: $endpoint belum mengembalikan HTTP 200 dengan database connected."
    }
}
