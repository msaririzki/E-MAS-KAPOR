<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        // Get 20 personnels to create testimonials
        $personnels = User::query()
            ->role('personil')
            ->orderBy('id')
            ->limit(20)
            ->get();

        if ($personnels->isEmpty()) {
            $this->command?->warn('TestimonialSeeder dilewati: belum ada user personil.');

            return;
        }

        // Varied Indonesian Testimonials per category
        $messagesTutupKepala = [
            'Topi dinas sangat pas ukurannya, nyaman dipakai seharian saat upacara maupun patroli.',
            'Kualitas tutup kepala sudah sangat baik, material tidak panas dan sirkulasi udara lancar.',
            'Helm dan topi yang diberikan sesuai dengan standar. Mantap!',
            'Ukuran topi sesuai form yang diisi. Tidak ada masalah.',
            'Baret dan topi dinas cocok semua. Proses distribusi juga cepat.',
        ];

        $messagesTutupBadan = [
            'Kemeja dinas pas di badan, jahitannya rapi dan material tidak mudah kusut.',
            'Jaket lapangan sangat nyaman dan tahan air. Kualitas premium.',
            'Baju PDH dan PDL lengkap dan ukurannya sesuai. Sangat puas.',
            'Seragam OKJ agak sedikit ketat tapi secara umum ok. Mungkin perlu varian ukuran lebih banyak.',
            'Semua item tutup badan diterima dengan baik, sesuai pesanan.',
        ];

        $messagesTutupKaki = [
            'Sepatu dinas sangat nyaman untuk kegiatan harian, sol bantalan empuk.',
            'Sepatu PDL kuat dan tahan lama, sudah dipakai berbulan-bulan masih bagus.',
            'Sepatu olahraga ringan dan nyaman untuk lari dan aktivitas fisik.',
            'Sepatu dinas agak sedikit keras di awal, tapi setelah beberapa hari sudah nyaman.',
            'Kualitas sepatu jauh lebih baik dari tahun lalu. Terima kasih tim logistik!',
        ];

        $genericMessages = [
            'Terima kasih atas pelayanan distribusi kapor yang sangat baik tahun ini.',
            'Sistem E-MAS KAPOR sangat membantu. Proses pendataan kapor jadi tertata rapi.',
            'Sangat inovatif dan paperless! Berhasil memangkas waktu birokrasi yang panjang.',
            'Akses dari HP sangat lancar, tidak perlu repot buka laptop untuk isi data ukuran.',
            'Luar biasa! Rekap data otomatisnya sangat akurat dan terpercaya.',
            'Sistem sangat stabil walau diakses bersamaan oleh ratusan personil.',
            'Semua data tersusun rapi. Membantu pimpinan dalam mengambil keputusan anggaran.',
            'Pengalaman pengguna (UX) patut diacungi jempol. Kelas nasional!',
            'Aman, cepat, dan presisi. Terbaik sejauh ini.',
            'Desain antarmuka memukau, maju terus Polri!',
        ];

        // Clear existing
        Testimonial::truncate();

        $count = 0;
        $categories = array_keys(Testimonial::CATEGORIES);

        foreach ($personnels as $index => $user) {
            // 1 person (index 10) gets 4-star ratings, rest get 5-star
            $is4Star = ($index === 10);
            $baseRating = $is4Star ? 4 : 5;

            $daysAgo = rand(1, 29);
            $submittedAt = Carbon::now()->subDays($daysAgo)->setTime(rand(8, 16), rand(0, 59));

            // Pick a shared message for this batch
            $message = $genericMessages[$index % count($genericMessages)];

            // Create 3 testimonials (one per category) per user
            foreach ($categories as $catIndex => $category) {
                // Slightly vary category rating for realism
                $rating = $baseRating;
                if (! $is4Star && $catIndex === 2 && $index % 5 === 0) {
                    $rating = 4; // some 4-star for tutup_kaki to add variety
                }

                Testimonial::create([
                    'user_id' => $user->id,
                    'category' => $category,
                    'message' => $message,
                    'rating' => $rating,
                    'created_at' => $submittedAt,
                    'updated_at' => $submittedAt,
                ]);

                $count++;
            }
        }

        $this->command?->info("Seeded $count testimonials (3 categories × {$personnels->count()} users).");
    }
}
