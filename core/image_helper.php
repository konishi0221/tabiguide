<?php
function resize_and_encode_images($files): array {
    $encodedImages = [];

    foreach ($files['tmp_name'] as $index => $tmpPath) {
        if ($index >= 10) break;

        $mime = mime_content_type($tmpPath);
        $ext = strtolower(pathinfo($files['name'][$index], PATHINFO_EXTENSION));

        $imagick = new Imagick();

        if ($mime === 'application/pdf') {
            $imagick->setResolution(150, 150);
            $imagick->readImage($tmpPath);
            $imagick->setImageFormat('jpeg');

            foreach ($imagick as $i => $page) {
                if (count($encodedImages) >= 10) break;
                $page->resizeImage(1000, 0, Imagick::FILTER_LANCZOS, 1);
                $imageBlob = $page->getImageBlob();
                $base64 = base64_encode($imageBlob);
                $encodedImages[] = [
                    'type' => 'image_url',
                    'image_url' => ['url' => 'data:image/jpeg;base64,' . $base64]
                ];
            }

        } else {
            $imagick->readImage($tmpPath);
            $imagick->resizeImage(1000, 0, Imagick::FILTER_LANCZOS, 1);
            $imagick->setImageFormat('jpeg');

            $imageBlob = $imagick->getImageBlob();
            $base64 = base64_encode($imageBlob);
            $encodedImages[] = [
                'type' => 'image_url',
                'image_url' => ['url' => 'data:image/jpeg;base64,' . $base64]
            ];
        }

        $imagick->clear();
        $imagick->destroy();
    }

    return $encodedImages;
}
