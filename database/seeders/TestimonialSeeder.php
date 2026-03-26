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
        // Get 20 personnels to create exactly 20 testimonials
        $personnels = User::query()
            ->role('personil')
            ->orderBy('id')
            ->limit(20)
            ->get();

        if ($personnels->isEmpty()) {
            $this->command?->warn('TestimonialSeeder dilewati: belum ada user personil.');

            return;
        }

        // Varied Indonesian Testimonials
        $messages5Star = [
            'Aplikasi ini mempermudah proses pendataan kapor di lapangan. Mantap!',
            'Sistemnya cepat dan responsif. Sangat membantu tugas kami di satker.',
            'Tampilan dashboard yang baru sangat informatif, transparan dan memanjakan mata.',
            'Proses input ukuran kapor sudah jauh lebih tertata. Koordinasi dari Polda hingga Polsek makin lancar.',
            'Apresiasi tinggi untuk tim pengembang Logistik. Sistem E-MAS KAPOR sangat revolusioner!',
            'UI/UX sangat modern, pengisian form juga mudah dipahami oleh seluruh anggota.',
            'Sangat inovatif dan paperless! Berhasil memangkas waktu birokrasi yang panjang.',
            'Akses dari HP sangat lancar, tidak perlu repot buka laptop lagi buat isi data ukuran.',
            'Luar biasa! Rekap data otomatisnya sangat akurat dan terpercaya.',
            'Birokrasi logistik jadi jauh lebih efisien. Fiturnya sangat relevan dengan kebutuhan lapangan.',
            'Sangat memuaskan! Data selalu terupdate secara real-time.',
            'Dengan aplikasi ini, masalah salah ukuran kaporlap hampir bisa dipastikan tidak ada lagi.',
            'Sistem sangat stabil walau diakses bersamaan oleh ratusan personil. Hebat!',
            'Notifikasinya sangat membantu mengingatkan personil yang lupa mengisi data.',
            'Semua data tersusun rapi. Sangat membantu pimpinan dalam mengambil keputusan anggaran.',
            'Pengalaman pengguna (UX) patut diacungi jempol. Kelas nasional!',
            'Terima kasih inovasinya. Benar-benar memudahkan kami para personil di satuan tingkat bawah.',
            'Aman, cepat, dan presisi. Terbaik sejauh ini.',
            'Desain antarmuka memukau, serasa pakai aplikasi startup modern. Maju terus Polri!',
        ];

        $messages4Star = [
            'Secara keseluruhan sangat bagus dan inovatif. Mungkin loading sedikit lama saat koneksi internet lemah di daerah pelosok.',
            'Sistem sudah berjalan dengan baik. Fiturnya lengkap, tinggal dibiasakan saja bagi personil yang lebih senior.',
            'Aplikasi E-MAS KAPOR sangat bermanfaat. Akan lebih sempurna jika ada fitur dark mode untuk akses di malam hari.',
        ];

        // Clear existing to ensure exact score
        Testimonial::truncate();

        $count = 0;
        foreach ($personnels as $index => $user) {
            // Give 19 people 5 stars, and 1 person 4 stars to ensure average is 4.95 => 99% score
            $is4Star = ($count === 10); // The 11th person gets 4 stars

            $rating = $is4Star ? 4 : 5;
            $msgArray = $is4Star ? $messages4Star : $messages5Star;

            // Pick a message
            $msgIndex = $is4Star ? array_rand($messages4Star) : ($count % count($messages5Star));
            $message = $msgArray[$msgIndex];

            $daysAgo = rand(1, 29);
            $submittedAt = Carbon::now()->subDays($daysAgo)->setTime(rand(8, 16), rand(0, 59));

            Testimonial::create([
                'user_id' => $user->id,
                'message' => $message,
                'rating' => $rating,
                'created_at' => $submittedAt,
                'updated_at' => $submittedAt,
            ]);

            $count++;
        }

        $this->command?->info("Seeded $count testimonials intentionally for a 99% score.");
    }
}
