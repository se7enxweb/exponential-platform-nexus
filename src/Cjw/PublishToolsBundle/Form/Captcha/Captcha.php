<?php

namespace Cjw\Cjw\PublishToolsBundle\Form\Captcha;

use Symfony\Component\HttpFoundation\StreamedResponse;

class Captcha
{
    public function buildAndRegisterCaptcha()
    {
        $path               = dirname( __FILE__).'/../../Resources/captcha';
        // Variablen (können angepasst werden) //
        $font_file 			= $path.'/font/railway-webfont.ttf';	// Pfad zur Schriftdatei
        $font_size			= 25; 										// Schriftgröße
        $text_angle			= mt_rand(0, 5);							// Schriftwinkel (Werte zwischen 0 und 5)
        $text_x				= mt_rand(0, 18);							// X-Position (Werte zwischen 0 und 18)
        $text_y				= 35;										// Y-Position
        $text_chars 		= 5;										// Länge des Textes
        $text_color			= array(mt_rand(0, 50), mt_rand(0, 50) , mt_rand(0, 50));							// Textfarbe (R, G, B)

        $captcha_bg_img_path    = $path.'/images/bgcaptcha/';
        $captcha_over_img_path  = $path.'/images/overcaptcha/';
        $captcha_bg_images 	    = array_values(array_diff( scandir($captcha_bg_img_path), array('..', '.'))); 						// Pfad zum Hintergrundbild
        $captcha_over_images 	= array_values(array_diff( scandir($captcha_over_img_path), array('..', '.'))); 						// Pfad zum Hintergrundbild


        $captcha_bg_img 	= $captcha_bg_img_path.$captcha_bg_images[mt_rand(0, (count($captcha_bg_images)-1))];
        if ( exif_imagetype( $captcha_bg_img )  != IMAGETYPE_PNG ) {
            $captcha_bg_img = $captcha_bg_img_path.'bg_captcha.png';
        }
        // Pfad zum Bild, was über das Captcha gelegt wird
        $captcha_over_img 	= $captcha_over_img_path.$captcha_over_images[mt_rand(0, (count($captcha_over_images)-1))];					// Pfad zum Bild, was über das Captcha gelegt wird
        // Pfad zum Bild, was über das Captcha gelegt wird
        if ( exif_imagetype($captcha_over_img) != IMAGETYPE_PNG ) {
            $captcha_over_img = $captcha_over_img.'bg_captcha_over.png';
        }

        // Funktion um zufälligen String zu generieren //
        $length=5;
        $letters = array_merge(range('A', 'H'), range('J', 'N'), range('P', 'Z'), range(2, 9)); // Verwendet keine 0, 1, I, O da sich diese ähnlich sehen
        $lettersCount = count($letters) - 1;
        $text = '';

        for ($i = 0; $i < $length; $i++) {
            $text .= $letters[mt_rand(0, $lettersCount)];
        }
        $length=7;
        $letters = array_merge(range('A','Z'), range(0, 9)); // Verwendet keine 0, 1, I, O da sich diese ähnlich sehen
        $lettersCount = count($letters) - 1;
        $filename = '';

        for ($i = 0; $i < $length; $i++) {
            $filename .= $letters[mt_rand(0, $lettersCount)];
        }

        // Zufälligen Text in der Session speichern //

        if (isset( $_SESSION['captcha_string'] ))
        {
            unset( $_SESSION['captcha_string'] );

        }
        $_SESSION['captcha_string'] = $text;

        // Captcha Bild erstellen, Text schreiben & Bild darüber legen //
        $img = ImageCreateFromPNG($captcha_bg_img);
        $text_color = ImageColorAllocate($img, $text_color[0], $text_color[1], $text_color[2]);

//            imagecopy($img, ImageCreateFromPNG($captcha_over_img), 0, 0, 0, 0, 140, 40);
        imagettftext($img, $font_size, $text_angle, $text_x, $text_y, -$text_color, $font_file, $text);
        imagecopymerge($img, ImageCreateFromPNG($captcha_over_img), 0, 0, 0, 0, 140, 40,mt_rand(10, 20));
//            imagettftext($img, $font_size, $text_angle, $text_x, $text_y, $text_color, $font_file, $text);
        ob_start(); // Let's start output buffering.
        imagejpeg($img); //This will normally output the image, but because of ob_start(), it won't.
        $contents = ob_get_contents(); //Instead, output above is saved to $contents
        ob_end_clean(); //End the output buffer.
        $dataUri = "data:image/jpeg;base64," . base64_encode($contents);
        // Ausgabe des Bildes //
        return $dataUri;
//        $dataUri = "data:image/jpeg;base64," . base64_encode($contents);
        // Ausgabe des Bildes //
//        $response =new Response($dataUri,200);
//        return new StreamedResponse(function () use ($img) {
//            return imagepng($img);
//        }, 200, ['Content-Type' => 'image/png']);

//            $response->headers->set('Content-Type','image/png');
//        return $response;
    }
    function isValid()
    {
        $validCaptcha = false;
        if ( isset( $_SESSION['captcha_solved'] ) && $_SESSION['captcha_solved'])
        {
            $validCaptcha = $_SESSION['captcha_solved'];
            unset( $_SESSION['captcha_solved'] );


            $_SESSION['captcha_string'] = "XXX";
            $_SESSION['captcha_solved'] = false;
        }
        return $validCaptcha;
    }
    static function checkCaptcha($userCaptcha, $sessionCaptcha) {
        $count = 0;
        for ($i = 0; $i < strlen($userCaptcha); $i++) {
            if (strpos($sessionCaptcha, $userCaptcha[$i]) !== false) {
                $count++;
            }
        }
        return $count === 3;
    }
}
