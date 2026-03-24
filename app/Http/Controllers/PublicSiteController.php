<?php

namespace App\Http\Controllers;

use App\Models\KaporItem;
use App\Models\Personnel;
use App\Models\Satker;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class PublicSiteController extends Controller
{
    public function home(Request $request): View
    {
        $baseUrl = $this->baseUrl($request);
        $metrics = $this->metrics();

        $seo = [
            'title' => 'Biro Logistik Polda NTB | E-MAS KAPOR',
            'description' => 'E-MAS KAPOR adalah sistem informasi manajemen kapor Biro Logistik Polda NTB untuk pendataan ukuran kapor personel, rekap satker, monitoring logistik, dan perencanaan distribusi yang lebih akurat, cepat, dan akuntabel.',
            'keywords' => implode(', ', $this->searchIntents()),
            'canonical' => $baseUrl.'/',
            'image' => $baseUrl.'/e-mas-kapor.png',
            'siteName' => 'E-MAS KAPOR',
            'domainHost' => parse_url($baseUrl, PHP_URL_HOST) ?: $baseUrl,
            'awardArticleUrl' => 'https://tribratanews.ntb.polri.go.id/inovasi-digital-e-mas-kapor-biro-logistik-polda-ntb-diganjar-penghargaan/',
        ];

        return view('public.home', [
            'seo' => $seo,
            'featureCards' => $this->featureCards(),
            'faqItems' => $this->faqItems(),
            'metricCards' => $this->metricCards($metrics),
            'metrics' => $metrics,
            'searchIntents' => $this->searchIntents(),
            'structuredData' => $this->structuredData($seo, $metrics),
            'testimonials' => $this->latestTestimonials(),
        ]);
    }

    public function sitemap(Request $request): Response
    {
        $baseUrl = $this->baseUrl($request);

        return response()
            ->view('public.sitemap', [
                'urls' => [
                    [
                        'loc' => $baseUrl.'/',
                        'changefreq' => 'weekly',
                        'priority' => '1.0',
                    ],
                ],
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(Request $request): Response
    {
        $baseUrl = $this->baseUrl($request);

        $lines = [
            'User-agent: *',
            'Allow: /',
            'Allow: /login',
            'Disallow: /admin',
            'Disallow: /admin/',
            'Disallow: /admin-satker',
            'Disallow: /admin-satker/',
            'Disallow: /dashboard',
            'Disallow: /dashboard/',
            'Disallow: /personil',
            'Disallow: /personil/',
            'Disallow: /profile',
            'Disallow: /profile/',
            'Disallow: /superadmin',
            'Disallow: /superadmin/',
            'Disallow: /logout',
            '',
            'Sitemap: '.$baseUrl.'/sitemap.xml',
        ];

        return response(implode(PHP_EOL, $lines).PHP_EOL, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    private function baseUrl(Request $request): string
    {
        $fallback = $request->getSchemeAndHttpHost();

        return rtrim(config('app.url', $fallback) ?: $fallback, '/');
    }

    private function metrics(): array
    {
        $metrics = [
            'satkerCount' => 0,
            'personnelCount' => 0,
            'activeItemCount' => 0,
            'testimonialCount' => 0,
            'averageRating' => null,
        ];

        try {
            if ($this->hasTables(['satkers'])) {
                $metrics['satkerCount'] = Satker::query()->count();
            }

            if ($this->hasTables(['personnels'])) {
                $metrics['personnelCount'] = Personnel::query()->count();
            }

            if ($this->hasTables(['kapor_items'])) {
                $metrics['activeItemCount'] = KaporItem::query()->where('is_active', true)->count();
            }

            if ($this->hasTables(['testimonials'])) {
                $metrics['testimonialCount'] = Testimonial::query()->count();

                if ($metrics['testimonialCount'] > 0) {
                    $metrics['averageRating'] = round(
                        (float) (Testimonial::query()
                            ->selectRaw('AVG(COALESCE(rating, 5)) as average_rating')
                            ->value('average_rating') ?? 0),
                        1,
                    );
                }
            }
        } catch (Throwable) {
            return $metrics;
        }

        return $metrics;
    }

    private function metricCards(array $metrics): array
    {
        return [
            [
                'value' => number_format($metrics['satkerCount']),
                'label' => 'Satker Terkelola',
                'description' => 'Satuan kerja yang dapat dipantau melalui rekap kapor dan kebutuhan logistik.',
            ],
            [
                'value' => number_format($metrics['personnelCount']),
                'label' => 'Data Personel',
                'description' => 'Basis data personel untuk input ukuran kapor, monitoring, dan penyusunan kebutuhan.',
            ],
            [
                'value' => number_format($metrics['activeItemCount']),
                'label' => 'Item Kapor Aktif',
                'description' => 'Referensi item perlengkapan perorangan yang dipakai dalam pendataan dan rekap.',
            ],
            [
                'value' => $metrics['averageRating'] !== null
                    ? number_format($metrics['averageRating'], 1).'/5'
                    : number_format($metrics['testimonialCount']),
                'label' => $metrics['averageRating'] !== null ? 'Skor Kepuasan' : 'Umpan Balik',
                'description' => $metrics['averageRating'] !== null
                    ? 'Ringkasan penilaian pengguna terhadap pengalaman memakai sistem E-MAS KAPOR.'
                    : 'Masukan pengguna yang dipakai untuk evaluasi layanan dan peningkatan kualitas sistem.',
            ],
        ];
    }

    private function latestTestimonials(): Collection
    {
        try {
            if (! $this->hasTables(['testimonials', 'users', 'satkers'])) {
                return collect();
            }

            return Testimonial::query()
                ->with(['user.satker'])
                ->whereNotNull('message')
                ->latest()
                ->take(3)
                ->get();
        } catch (Throwable) {
            return collect();
        }
    }

    private function featureCards(): array
    {
        return [
            [
                'title' => 'Pendataan Ukuran Kapor',
                'description' => 'Personel dapat mengisi data ukuran kapor secara cepat sehingga proses rekap lebih rapi dan minim salah input.',
            ],
            [
                'title' => 'Rekap Per Satker',
                'description' => 'Operator dapat memantau kelengkapan data setiap satker untuk mempercepat validasi dan sinkronisasi kebutuhan.',
            ],
            [
                'title' => 'Monitoring Logistik',
                'description' => 'Biro Logistik memiliki fondasi data yang lebih kuat untuk melihat kebutuhan, kesiapan distribusi, dan evaluasi layanan.',
            ],
            [
                'title' => 'Perencanaan Akuntabel',
                'description' => 'Data yang tertata memudahkan penyusunan laporan, ekspor rekap, audit trail, dan pengambilan keputusan yang presisi.',
            ],
        ];
    }

    private function searchIntents(): array
    {
        return [
            'biro logistik polda ntb',
            'biro log polda ntb',
            'e-mas kapor polda ntb',
            'login e-mas kapor',
            'sistem informasi manajemen kapor',
            'data ukuran kapor personel',
            'rekap kapor satker',
            'perencanaan logistik polri ntb',
        ];
    }

    private function faqItems(): array
    {
        return [
            [
                'question' => 'Apa itu E-MAS KAPOR Biro Logistik Polda NTB?',
                'answer' => 'E-MAS KAPOR adalah sistem informasi berbasis web untuk pendataan ukuran kapor personel, rekap per satker, monitoring logistik, dan dukungan perencanaan distribusi perlengkapan dinas di lingkungan Polda NTB.',
            ],
            [
                'question' => 'Siapa yang biasa mencari dan mengakses sistem ini?',
                'answer' => 'Pencarian biasanya datang dari personel yang ingin login, operator satker yang mencari rekap data kapor, serta pihak internal yang membutuhkan informasi tentang sistem manajemen kapor Biro Logistik Polda NTB.',
            ],
            [
                'question' => 'Kenapa halaman utama dibuat terpisah dari halaman login?',
                'answer' => 'Halaman publik diperlukan agar Google memahami konteks sistem, nama instansi, dan manfaat layanan. Halaman login tetap penting untuk akses pengguna, tetapi bukan target utama untuk pencarian organik.',
            ],
            [
                'question' => 'Apa manfaat SEO untuk domain resmi Biro Logistik Polda NTB?',
                'answer' => 'SEO yang tepat membantu domain resmi lebih mudah ditemukan saat orang mencari nama instansi, E-MAS KAPOR, login sistem, data ukuran kapor personel, dan topik logistik terkait di wilayah Polda NTB.',
            ],
        ];
    }

    private function structuredData(array $seo, array $metrics): array
    {
        $organization = [
            '@context' => 'https://schema.org',
            '@type' => 'GovernmentOrganization',
            'name' => 'Biro Logistik Polda NTB',
            'url' => $seo['canonical'],
            'logo' => $seo['image'],
            'description' => $seo['description'],
            'parentOrganization' => [
                '@type' => 'GovernmentOrganization',
                'name' => 'Polda Nusa Tenggara Barat',
            ],
            'areaServed' => [
                [
                    '@type' => 'AdministrativeArea',
                    'name' => 'Nusa Tenggara Barat',
                ],
            ],
            'sameAs' => [
                'https://tribratanews.ntb.polri.go.id/',
            ],
        ];

        $website = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'E-MAS KAPOR',
            'url' => $seo['canonical'],
            'inLanguage' => 'id-ID',
            'description' => $seo['description'],
        ];

        $softwareApplication = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'E-MAS KAPOR',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'url' => $seo['canonical'],
            'description' => $seo['description'],
            'provider' => [
                '@type' => 'GovernmentOrganization',
                'name' => 'Biro Logistik Polda NTB',
            ],
            'featureList' => [
                'Pendataan ukuran kapor personel',
                'Rekap per satker',
                'Monitoring logistik',
                'Ekspor laporan dan evaluasi layanan',
            ],
        ];

        if ($metrics['averageRating'] !== null && $metrics['testimonialCount'] > 0) {
            $softwareApplication['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $metrics['averageRating'],
                'bestRating' => 5,
                'ratingCount' => $metrics['testimonialCount'],
            ];
        }

        return [$organization, $website, $softwareApplication];
    }

    private function hasTables(array $tables): bool
    {
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }
}
