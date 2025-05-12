<?php

namespace Faker\Provider\zh_CN;

class Image extends \Faker\Provider\Image
{
    public const BASE_URL = 'https://picsum.photos';

    public const FORMAT_JPG = 'jpg';
    public const FORMAT_WEBP = 'webp';

    /**
     * Generate the URL that will return a random image
     *
     * Set randomize to false to remove the random GET parameter at the end of the url.
     *
     * @example 'https://picsum.photos/640/480.jpg/CCCCCC?text=well+hi+there'
     *
     * @param int         $width
     * @param int         $height
     * @param string|null $category
     * @param bool        $randomize
     * @param string|null $word
     * @param bool        $gray
     * @param string      $format
     *
     * @return string
     */
    public static function imageUrl(
        $width = 640,
        $height = 480,
        $category = null,
        $randomize = true,
        $word = null,
        $gray = false,
        $format = 'webp'
    ) {
        // Validate image format
        $imageFormats = static::getFormats();

        if (!in_array(strtolower($format), $imageFormats, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid image format "%s". Allowable formats are: %s',
                $format,
                implode(', ', $imageFormats),
            ));
        }

        $id = '';
        $category = (int)$category;
        if ($category > 0 && $category < 1085) {
            $id = sprintf('/id/%d', $category);
        } elseif ($randomize) {
            $id = sprintf('/id/%d', random_int(1, 1084));
        }
        $size = sprintf('/%d/%d', $width, $height);

        $grayscale = '';
        if ($gray === true) {
            $grayscale = 'grayscale';
        }

        return self::BASE_URL . $id . $size . '.' . $format . '?' . $grayscale;
    }

    public static function image(
        $dir = null,
        $width = 640,
        $height = 480,
        $category = null,
        $fullPath = true,
        $randomize = true,
        $word = null,
        $gray = false,
        $format = 'webp'
    ) {
        $dir = is_null($dir) ? sys_get_temp_dir() : $dir; // GNU/Linux / OS X / Windows compatible
        // Validate directory path
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new \InvalidArgumentException(sprintf('Cannot write to directory "%s"', $dir));
        }

        // Generate a random filename. Use the server address so that a file
        // generated at the same time on a different server won't have a collision.
        $name     = md5(uniqid(empty($_SERVER['SERVER_ADDR']) ? '' : $_SERVER['SERVER_ADDR'], true));
        $filename = $name . '.' . $format;
        $filepath = $dir . DIRECTORY_SEPARATOR . $filename;

        $url = static::imageUrl($width, $height);

        // save file
        if (function_exists('curl_exec')) {
            // use cURL
            $fp = fopen($filepath, 'w');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
//            curl_setopt($ch, CURLOPT_VERBOSE, true);
            $success = curl_exec($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
            fclose($fp);
            curl_close($ch);

            if (!$success) {
                unlink($filepath);

                // could not contact the distant URL or HTTP error - fail silently.
                return false;
            }
        }
        elseif (ini_get('allow_url_fopen')) {
            // use remote fopen() via copy()
            copy($url, $filepath);
        }
        else {
            return new \RuntimeException('The image formatter downloads an image from a remote HTTP server. Therefore, it requires that PHP can request remote hosts, either via cURL or fopen()');
        }

        return $fullPath ? $filepath : $filename;
    }

    public static function getFormats(): array
    {
        return array_keys(static::getFormatConstants());
    }

    public static function getFormatConstants(): array
    {
        return [
            static::FORMAT_JPG => constant('IMAGETYPE_JPEG'),
            static::FORMAT_WEBP => constant('IMAGETYPE_WEBP'),
        ];
    }
}
