<?php
// lightweight test for carousel helper without pulling in entire index.php
function getCarouselImages($dir = 'images/chocolat') {
    $result = [];
    $absolute = __DIR__ . '/' . $dir;
    if (is_dir($absolute)) {
        $patterns = [
            $absolute . '/*.jpg',
            $absolute . '/*.jpeg',
            $absolute . '/*.png',
            $absolute . '/*.gif',
        ];
        foreach ($patterns as $pattern) {
            foreach (glob($pattern) as $path) {
                $result[] = str_replace('\\', '/', substr($path, strlen(__DIR__) + 1));
            }
        }
    }
    if (empty($result)) {
        $result[] = 'images/default-promo.jpg';
    }
    return $result;
}

$images = getCarouselImages('images/chocolat');
echo "Carousel images:\n";
print_r($images);
?>