param(
    [string]$RepoRoot = (Split-Path -Parent $PSScriptRoot)
)

$childTheme = Join-Path $RepoRoot 'child-theme'
$muPlugins  = Join-Path $RepoRoot 'mu-plugins'
$purgeFile  = Join-Path $PSScriptRoot 'purge-litespeed.php'
$assetRoot  = Join-Path $childTheme 'assets'

scp (Join-Path $childTheme 'functions.php') crystalcentury-user:public_html/wp-content/themes/hello-elementor-child/functions.php
scp (Join-Path $childTheme 'style.css') crystalcentury-user:public_html/wp-content/themes/hello-elementor-child/style.css
scp (Join-Path $muPlugins 'perf-tweaks.php') crystalcentury-user:public_html/wp-content/mu-plugins/perf-tweaks.php
scp (Join-Path $muPlugins 'arby-rollout-core.php') crystalcentury-user:public_html/wp-content/mu-plugins/arby-rollout-core.php
scp $purgeFile crystalcentury-user:/tmp/cc-purge-litespeed.php

if (Test-Path $assetRoot) {
    ssh crystalcentury-user "mkdir -p public_html/wp-content/themes/hello-elementor-child/assets"
    Get-ChildItem -Path $assetRoot -File | ForEach-Object {
        $remotePath = "crystalcentury-user:public_html/wp-content/themes/hello-elementor-child/assets/$($_.Name)"
        scp $_.FullName $remotePath
    }
}

ssh crystalcentury-user "bash -lc 'cd ~/public_html && wp --allow-root cache flush && wp --allow-root eval-file /tmp/cc-purge-litespeed.php'"
