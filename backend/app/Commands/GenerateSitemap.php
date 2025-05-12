<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Generate a sitemap for the application';

    public function handle()
    {
        // Logique pour générer le sitemap
        $sitemapContent = "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap-image/1.1'>\n";
        
        // Exemple d'ajout d'URL
        $sitemapContent .= "  <url>\n";
        $sitemapContent .= "    <loc>" . url('/') . "</loc>\n";
        $sitemapContent .= "  </url>\n";
        $sitemapContent .= "</urlset>";

        Storage::disk('public')->put('sitemap.xml', $sitemapContent);
        $this->info('Sitemap generated successfully.');

        return 0;
    }
};