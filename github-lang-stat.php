<?php

//Fungsi untuk memeriksa apakah file cache sudah kadaluarsa
function isCacheExpired($cacheFile) {
    //Jika file cache tidak ada, return true (cache expired)
    if (!file_exists($cacheFile)) {
        return true;
    }
    
    //Ambil waktu modifikasi terakhir dari file cache
    $lastModifiedTime = filemtime($cacheFile);
    
    //Perhitungan waktu 5 jam dalam detik
    $fiveHoursAgo = time() - (5 * 3600);
    
    //Jika waktu modifikasi terakhir lebih dari 5 jam yang lalu, return true (cache expired)
    if ($lastModifiedTime < $fiveHoursAgo) {
        return true;
    }
    
    //Jika tidak, return false (cache masih valid)
    return false;
}

//Fungsi untuk membuat file cache
function createCache($cacheFile, $data) {
    //Simpan data ke dalam file cache dalam bentuk serialisasi
    file_put_contents($cacheFile, serialize($data));
}

//Mengambil data dari cache jika cache masih valid
$cacheFile = 'github_cache.txt';
if (!isCacheExpired($cacheFile)) {
    //Jika cache masih valid, baca data dari cache
    $githubLanguages = unserialize(file_get_contents($cacheFile));
} else {
    // Jika cache expired, lakukan permintaan ke API GitHub
    $token = "github_token";
    $username = "habbatul";
    $url = "https://api.github.com/user/repos";
    // $url = "https://api.github.com/users/habbatul/repos";
    
    $headers = array(
        "Accept: application/vnd.github+json",
        "Authorization: Bearer $token",
        "User-Agent: My-App" 
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $githubData = json_decode($response, true);

    $unwantedRepos = ['cropperGambar','Portofolio-Website', 'github-readme-stats']; 
    $unwantedLanguage = ['SCSS'];
    
    //Proses data dari API GitHub
    if ($githubData !== null) {
        $githubLanguages = [];
        foreach ($githubData as $repo) {
            $repoName = $repo['name'];
            
            if (in_array($repoName, $unwantedRepos)) {
                continue;
            }

            $languagesUrl = "https://api.github.com/repos/$username/$repoName/languages";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $languagesUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $languagesResponse = curl_exec($ch);
            curl_close($ch);
            $repoLanguages = json_decode($languagesResponse, true);
    
            //Mengumpulkan informasi bahasa dari setiap repositori
            foreach ($repoLanguages as $lang => $bytes) {
                if (!in_array($lang, $unwantedLanguage)) {
                    if (isset($githubLanguages[$lang])) {
                        $githubLanguages[$lang]++;
                    } else {
                        $githubLanguages[$lang] = 1;
                    }
                }
            }
        }
        //Membuat file cache dengan data yang baru didapat
        createCache($cacheFile, $githubLanguages);
    } else {
        $githubLanguages = [];
    }
}
?>
<?php
//Fungsi untuk menghasilkan warna secara acak dalam format hex
function generateColor() {
    $r = mt_rand(200, 255);
    $g = mt_rand(200, 255);
    $b = mt_rand(200, 255);
    
    //Menggabungkan nilai RGB menjadi format hex
    $color = sprintf("#%02x%02x%02x", $r, $g, $b);
    return $color;
}
header("Content-type: image/svg+xml"); ?>
<svg xmlns="http://www.w3.org/2000/svg" width="350" height="220">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;700');
        .header {
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            font-weight: bold;
            text-anchor: middle;
            fill: #fff; /* Warna teks putih */
            /* Animasi transisi */
            opacity: 0;
            animation: fadeInB 1s ease forwards;
        }
        .background {
            fill: url(#grad);
            /* Animasi transisi */
            opacity: 0;
            animation: fadeIn 1s ease forwards;
        }

        .watermark {
            opacity: 0;
            animation: fadeInC 2s ease forwards;
            font-size: 10px;
            font-family: 'Poppins', sans-serif;
        }

        /* Keyframes untuk animasi fadeIn */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        @keyframes fadeInB {
            from {
                transform: translate(-70%, 0);
                opacity: 0;
            }
            to {
                transform: translate(0, 0);
                opacity: 1;
            }
        }

        @keyframes fadeInC {
            from {
                transform: translate(0, 50%);
                opacity: 0;
            }
            to {
                transform: translate(0, 0);
                opacity: 1;
            }
        }
    </style>
    <defs>
        <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="0%">

            <stop offset="0%" style="stop-color:#6A1C94;stop-opacity:1" />

            <stop offset="100%" style="stop-color:#AA3623;stop-opacity:1" />
        </linearGradient>
    </defs>

    <rect x="0" y="0" width="350" height="220" rx="20" ry="20" class="background"/>
    <rect class="watermark" x="36%" y="91%" width="100" height="15" rx="2" ry="2" fill="#fff"/>

    <text x="50%" y="26" class="header">Used Languages</text>

    <text x="132" y="211" fill="#6A1C94" class="watermark">Project By @hq.han</text>

    <?php $count = 0; ?>
    <?php foreach ($githubLanguages as $lang => $jumlah): ?>
    <?php
        // Tentukan posisi x dan y
        $x = $count % 2 == 0 ? 20 : 178;
        $y = 70 + floor($count / 2) * 25;
        ?>
        <text x="<?php echo $x; ?>" y="<?php echo $y; ?>" class="language" style="font-family: 'Poppins', sans-serif;font-size: 14px;text-anchor: start;" fill="<?=generateColor()?>"><?php echo "⦿ $lang ($jumlah repos)"; ?></text>
        <?php $count++; ?>
    <?php endforeach;?>
</svg>
