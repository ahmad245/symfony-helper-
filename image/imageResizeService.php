<?php
namespace UfmcpBundle\Service;

class ImageResizer
{
    public function resizeTerritoireBanniere($filepath, $w, $h) {
        if(is_file($filepath)) {
            $file = imagecreatefrompng($filepath);
            $size = getimagesize($filepath);

            if($size[1] > $h) {
                $new_w = (int)$size[0]*$h/$size[1];
                $temp = imagecreatetruecolor($new_w, $h);
                imagecopyresized(
                    $temp,
                    $file,
                    0, 0,
                    0, 0,
                    $new_w, $h,
                    $size[0], $size[1]
                );
                $file = $temp;
            }

            $size = [ imagesx($file), imagesy($file) ];
            $dest = [ (int)(($w - $size[0])/2), (int)(($h - $size[1])/2) ];

            $temp = imagecreatetruecolor($w, $h);

            imagetruecolortopalette($temp, false, 255);
            $index = imagecolorclosest($temp, 0, 0, 0);
            imagecolorset($temp, $index, 255, 255, 255);
            imagepalettetotruecolor($temp);

            imagecopyresized(
                $temp,
                $file,
                $dest[0], $dest[1],
                0, 0,
                $size[0], $size[1],
                $size[0], $size[1]
            );
            $size = [ imagesx($temp), imagesy($temp) ];
            $new = $temp;

            if($size[0] > $w) {
                $new_h = $size[1]*$w/$size[0];
                $temp = imagecreatetruecolor($w, $new_h);

                imagetruecolortopalette($temp, false, 255);
                $index = imagecolorclosest($temp, 0, 0, 0);
                imagecolorset($temp, $index, 255, 255, 255);
                imagepalettetotruecolor($temp);

                imagecopyresized(
                    $temp,
                    $file,
                    0, 0,
                    0, 0,
                    $w, $new_h,
                    $w, $new_h
                );
                $new = $temp;
            }

            imagepng($new, $filepath);
            imagedestroy($new);
            imagedestroy($file);
        }
    }

    /**
     * Redimensionne l'image en 1100x85
     * @param $filepath string
     */
    public function resizeTerritoireHeader($filepath) {
        $this->resizeTerritoireBanniere($filepath, 1100, 85);
    }

    /**
     * Redimensionne l'image en 1100x135
     * @param $filepath string
     */
    public function resizeTerritoireFooter($filepath) {
        $this->resizeTerritoireBanniere($filepath, 1100, 135);
    }
    
    /**
     * Redimensionne l'image en 462x290
     * @param $filepath string
     */
    public function resizeTerritoireTampon($filepath) {
        $this->resizeTerritoireBanniere($filepath, 462, 290);
    }

    /**
     * Redimensionne l'image si elle n'est pas assez large
     * @param $filepath
     */
    public function resizeTerritoirePlan($filepath) {
        if(is_file($filepath)) {
            $min_ratio = 1.7;
            $file = imagecreatefrompng($filepath);
            $size = getimagesize($filepath);
            if($size[0] / $size[1] < $min_ratio) {
                // remplacement de la couleur noir pour gestion de la transparence
                imagetruecolortopalette($file, false, 255);
                $index = imagecolorclosest($file, 0, 0, 0);
                imagecolorset($file, $index, 0, 255, 0);

                // on concerve la hauteur mais on augmente la largeur
                $width = $size[1] * $min_ratio;
                $new = imagecreatetruecolor($width, $size[1]);
                imagecopyresampled(
                    $new,
                    $file,
                    (int)(($width - $size[0])/2), 0,
                    0, 0,
                    $size[0], $size[1],
                    $size[0], $size[1]
                );

                imagecolortransparent($new, imagecolorallocate($new,0,0,0));
                imagetruecolortopalette($new, false, 255);
                $index = imagecolorclosest($new, 0, 255, 0);
                imagecolorset($new, $index, 0, 0, 0);

                imagepng($new, $filepath);
                imagedestroy($new);
            }
            imagedestroy($file);
        }
    }
}