<?php
// Function to update logo across all necessary directories
function processAndSaveLogo($uploadedTmpPath, $ext) {
    $projectRoot = dirname(__DIR__);
    $dirs = [
        $projectRoot . '/public',
        $projectRoot . '/netspace/assets',
        $projectRoot,
        $projectRoot . '/dist'
    ];

    foreach ($dirs as $d) {
        if (!is_dir($d)) {
            @mkdir($d, 0777, true);
        }
    }

    $rawContent = file_get_contents($uploadedTmpPath);
    if (!$rawContent) {
        throw new Exception("Unable to read uploaded file.");
    }

    // Save main logo.png in all directories
    foreach ($dirs as $d) {
        if (is_dir($d)) {
            file_put_contents($d . '/logo.png', $rawContent);
            if ($ext === 'svg') {
                file_put_contents($d . '/logo.svg', $rawContent);
            }
        }
    }

    // If GD is available and not an SVG, create transparent variants
    if (extension_loaded('gd') && $ext !== 'svg') {
        $src = @imagecreatefromstring($rawContent);
        if ($src) {
            $w = imagesx($src);
            $h = imagesy($src);

            $darkLogo = imagecreatetruecolor($w, $h);
            imagealphablending($darkLogo, false);
            imagesavealpha($darkLogo, true);
            $trans = imagecolorallocatealpha($darkLogo, 0, 0, 0, 127);
            imagefill($darkLogo, 0, 0, $trans);

            $lightLogo = imagecreatetruecolor($w, $h);
            imagealphablending($lightLogo, false);
            imagesavealpha($lightLogo, true);
            imagefill($lightLogo, 0, 0, $trans);

            for ($x = 0; $x < $w; $x++) {
                for ($y = 0; $y < $h; $y++) {
                    $rgb = imagecolorat($src, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $bright = ($r + $g + $b) / 3;

                    if ($bright < 210) {
                        $alpha = (int)(($bright / 210) * 127);
                        $wCol = imagecolorallocatealpha($darkLogo, 240, 246, 252, $alpha);
                        imagesetpixel($darkLogo, $x, $y, $wCol);

                        $bCol = imagecolorallocatealpha($lightLogo, 15, 23, 42, $alpha);
                        imagesetpixel($lightLogo, $x, $y, $bCol);
                    }
                }
            }

            foreach ($dirs as $d) {
                if (is_dir($d)) {
                    imagepng($darkLogo, $d . '/logo-white.png');
                    imagepng($lightLogo, $d . '/logo-dark.png');
                }
            }
            imagedestroy($darkLogo);
            imagedestroy($lightLogo);
            imagedestroy($src);
        }
    }
}
